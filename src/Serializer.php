<?php

declare(strict_types=1);

namespace Rhinox\JsonApi;

use Rhinox\JsonApi\Exception\SerializerException;

class Serializer implements \JsonSerializable
{
    protected ?array $meta = null;
    protected ?array $included = [];
    protected Define $define;

    public function __construct()
    {
        $this->meta = [];
        $this->define = new Define();
    }

    public function serializeSingle($entity): array
    {
        $this->validateEntity($entity);
        return $this->serialize($entity, false);
    }

    public function serializeMultiple(iterable $entities): array
    {
        return $this->serialize($entities, true);
    }

    public function jsonSerialize(): mixed
    {
        return $this->serialize(null, false);
    }

    protected function serialize($entities, bool $isCollection): array
    {
        $meta = new JsonObject($this->meta ?: [], false);

        if ($entities === null) {
            return [
                'meta' => $meta,
                'data' => null,
            ];
        }

        $stack = [];
        if ($isCollection) {
            if (count($entities) === 0) {
                return [
                    'meta' => $meta,
                    'data' => [],
                ];
            }
            foreach ($entities as $entity) {
                $stack[] = [$this, $entity];
            }
        } else {
            $stack[] = [$this, $entities];
        }

        $fetchedEntities = [];
        do {
            [$serializer, $entity] = array_shift($stack);
            $id = $serializer->getId($entity);
            $type = $serializer->getType($entity);
            $includedKey = $type . ':' . $id;

            if (!isset($fetchedEntities[$includedKey])) {
                $attributes = $serializer->getAttributes($entity);

                $this->validateAttributes($attributes);

                $fetchedEntities[$includedKey] = [
                    'id' => (string) $id,
                    'type' => $type,
                    'attributes' => empty($attributes) ? new \stdClass() : $attributes,
                    'relationships' => [],
                ];

                foreach ($serializer->iterateRelationships($entity) ?: [] as $relationshipName => $relationshipDefs) {
                    [$relatedSerializerClass, $relatedEntitiesCallback, $constructorParameters] = $relationshipDefs + [null, null, []];

                    if ($this->isIncluded($type, $relationshipName)) {
                        $relatedEntities = $relatedEntitiesCallback();

                        if (!is_iterable($relatedEntities)) {
                            $relatedEntity = $relatedEntities;
                            if (!$relatedEntity) {
                                continue;
                            }

                            $relatedSerializer = new $relatedSerializerClass($relatedEntity, null, ...$constructorParameters);
                            $relatedSerializer->setIncluded($this->included);

                            $fetchedEntities[$includedKey]['relationships'][$relationshipName] = [
                                'data' => [
                                    'id' => (string) $relatedSerializer->getId($relatedEntity),
                                    'type' => $relatedSerializer->getType($relatedEntity),
                                ],
                            ];

                            $relatedIncludedKey = $relatedSerializer->getType($relatedEntity) . ':' . $relatedSerializer->getId($relatedEntity);
                            if (!isset($fetchedEntities[$relatedIncludedKey])) {
                                $stack[] = [$relatedSerializer, $relatedEntity];
                            }
                        } else {
                            foreach ($relatedEntities as $relatedEntity) {
                                if (!$relatedEntity) {
                                    continue;
                                }

                                $relatedSerializer = new $relatedSerializerClass($relatedEntity, null, ...$constructorParameters);
                                $relatedSerializer->setIncluded($this->included);

                                if (!isset($fetchedEntities[$includedKey]['relationships'][$relationshipName])) {
                                    $fetchedEntities[$includedKey]['relationships'][$relationshipName] = [
                                        'data' => [],
                                    ];
                                }

                                $fetchedEntities[$includedKey]['relationships'][$relationshipName]['data'][] = [
                                    'id' => (string) $relatedSerializer->getId($relatedEntity),
                                    'type' => $relatedSerializer->getType($relatedEntity),
                                ];

                                $relatedIncludedKey = $relatedSerializer->getType($relatedEntity) . ':' . $relatedSerializer->getId($relatedEntity);
                                if (!isset($fetchedEntities[$relatedIncludedKey])) {
                                    $stack[] = [$relatedSerializer, $relatedEntity];
                                }
                            }
                        }
                    }
                }
            }
        } while (!empty($stack));

        foreach ($fetchedEntities as $includedId => $fetchedEntity) {
            if (empty($fetchedEntities[$includedId]['relationships'])) {
                unset($fetchedEntities[$includedId]['relationships']);
            }
        }

        if ($isCollection) {
            $data = [];
            foreach ($entities as $entity) {
                $includedKey = $this->getType($entity) . ':' . $this->getId($entity);
                if (isset($fetchedEntities[$includedKey])) {
                    $data[] = $fetchedEntities[$includedKey];
                    unset($fetchedEntities[$includedKey]);
                }
            }
        } else {
            $includedKey = $this->getType($entities) . ':' . $this->getId($entities);
            $data = $fetchedEntities[$includedKey];
            unset($fetchedEntities[$includedKey]);
        }

        $jsonApiData = [
            'meta' => $meta,
            'data' => $data,
        ];

        $jsonApiData['included'] = array_values($fetchedEntities);

        return $jsonApiData;
    }

    public function setIncluded(?array $included = null): static
    {
        $this->included = $included;
        return $this;
    }

    protected function getId($entity): string
    {
        if ($entity instanceof SerializableInterface) {
            return $entity->getId();
        }

        if (is_array($entity) && isset($entity['id'])) {
            return (string) $entity['id'];
        }

        if (is_object($entity) && method_exists($entity, 'getId')) {
            return (string) $entity->getId();
        }

        if (is_object($entity) && isset($entity->id)) {
            return (string) $entity->id;
        }

        throw new SerializerException('Entity must implement SerializableInterface or have an id property/method');
    }

    protected function getType($entity): string
    {
        if (is_array($entity)) {
            return 'array';
        }

        return (new \ReflectionClass($entity))->getShortName();
    }

    protected function getAttributes($entity): array
    {
        $result = [];
        foreach ($this->defineAttributes() as $key => $definition) {
            $result[$key] = $definition->getValue($entity);
        }
        return $result;
    }

    protected function iterateRelationships($entity): ?iterable
    {
        return null;
    }

    protected function validateEntity($entity): bool
    {
        if ($entity === null) {
            throw new SerializerException('Entity cannot be null');
        }
        if (is_scalar($entity)) {
            throw new SerializerException('Entity cannot be a scalar value');
        }
        if (!is_array($entity) && !is_object($entity)) {
            throw new SerializerException('Entity must be an array or object');
        }

        return true;
    }

    protected function validateAttributes(array $attributes): void
    {
        if (isset($attributes['id'])) {
            throw new SerializerException('Attribute "id" is a reserved word');
        }
        if (isset($attributes['type'])) {
            throw new SerializerException('Attribute "type" is a reserved word');
        }
        if (isset($attributes['relationships'])) {
            throw new SerializerException('Attribute "relationships" is a reserved word');
        }
    }

    protected function isIncluded(string $type, string $relationshipName): bool
    {
        if ($this->included === null) {
            return true;
        }

        return in_array($type . '.' . $relationshipName, $this->included);
    }

    public function setMeta(array $meta): static
    {
        $this->meta = $meta;
        return $this;
    }

    public function addMeta(string $key, $value): static
    {
        if ($this->meta === null) {
            $this->meta = [];
        }
        $this->meta[$key] = $value;
        return $this;
    }

    public function getMeta(): array
    {
        return $this->meta ?? [];
    }

    protected function isIterable($entities): bool
    {
        return is_iterable($entities);
    }

    protected function isCollection($entities): bool
    {
        if (!is_array($entities)) {
            return $entities instanceof \Traversable;
        }

        if (empty($entities)) {
            return true;
        }

        return array_is_list($entities);
    }

    protected function relationship(string $serializerClass, callable $accessorCallback, array $constructorParameters = []): array
    {
        return [$serializerClass, $accessorCallback, $constructorParameters];
    }
}
