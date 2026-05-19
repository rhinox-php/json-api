<?php

declare(strict_types=1);

namespace Rhinox\JsonApi;

use Rhino\InputData\InputData;
use Rhinox\JsonApi\Exception\SerializerException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class Parser
{
    protected ValidatorInterface $validator;

    public function __construct(
        protected Serializer $serializer,
        ?ValidatorInterface $validator = null,
    ) {
        $this->validator = $validator ?? Validation::createValidator();
    }

    public function parse(object $entity, InputData $document): void
    {
        $this->validateDocument($document);
        $this->parseResource($entity, $document->arr('data', []), $document);
    }

    public function parseResource(object $entity, InputData $resource, ?InputData $document = null): void
    {
        $this->validateResource($entity, $resource);
        $this->parseAttributes($entity, $resource->arr('attributes', []));
        $this->parseRelationships($entity, $resource->arr('relationships', []));
    }

    public function parseAttributes(object $entity, InputData $inputAttributes): void
    {
        $this->assertObject($inputAttributes, 'attributes');
        $this->assertKnownKeys($inputAttributes, $this->attributeDefinitions(), 'attributes');

        foreach ($this->serializer->defineAttributes() as $attributeName => $definition) {
            $definition = $this->definitionObject($definition);
            if (!$inputAttributes->exists($attributeName)) {
                if ($definition->isRequired()) {
                    throw new SerializerException(sprintf('JSON:API attribute "%s" is required', $attributeName));
                }
                continue;
            }

            $value = $inputAttributes->raw($attributeName);
            if (method_exists($definition, 'getConstraints')) {
                $this->assertValid($value, $definition->getConstraints(), 'attributes.' . $attributeName);
            }
            if (method_exists($definition, 'setValue')) {
                $definition->setValue($entity, $value);
            }
        }
    }

    public function parseRelationships(object $entity, InputData $relationships): void
    {
        $this->assertObject($relationships, 'relationships');
        $this->assertKnownKeys($relationships, $this->relationshipDefinitions(), 'relationships');

        foreach ($this->serializer->defineRelationships() as $relationshipName => $definition) {
            $definition = $this->definitionObject($definition);
            if (!$relationships->exists($relationshipName)) {
                if (method_exists($definition, 'isRequired') && $definition->isRequired()) {
                    throw new SerializerException(sprintf('JSON:API relationship "%s" is required', $relationshipName));
                }
                continue;
            }

            $relationship = $relationships->arr($relationshipName, []);
            $this->validateRelationship($relationshipName, $definition, $relationship);

            if (method_exists($definition, 'setValue')) {
                $definition->setValue(
                    $entity,
                    $relationship->string('data.id', null),
                    $relationship->string('data.type', null),
                    $relationship,
                );
            }
        }
    }

    public function parseMultipleIncluded(
        string $serializerClass,
        string $entityClass,
        array $existingEntities,
        InputData $relationship,
        InputData $included,
    ): array {
        if (!is_subclass_of($serializerClass, Serializer::class)) {
            throw new SerializerException(sprintf('Included serializer "%s" must extend %s', $serializerClass, Serializer::class));
        }
        if (!class_exists($entityClass)) {
            throw new SerializerException(sprintf('Included entity class "%s" does not exist', $entityClass));
        }

        $includedParser = new self(new $serializerClass(), $this->validator);
        $includedType = $this->typeForClass($entityClass);

        $result = [];
        foreach ($this->dataArray($relationship->arr('data', []), 'relationship data') as $identifier) {
            $id = $identifier->string('id', null);
            if ($identifier->string('type', null) !== $includedType || $id === null) {
                continue;
            }

            $entity = $this->findEntity($existingEntities, $id) ?? new $entityClass();
            foreach ($this->dataArray($included, 'included') as $includedResource) {
                if ($includedResource->string('id', null) === $id && $includedResource->string('type', null) === $includedType) {
                    $includedParser->parseResource($entity, $includedResource);
                    $result[] = $entity;
                    break;
                }
            }
        }

        return $result;
    }

    protected function validateResource(object $entity, InputData $resource): void
    {
        $this->assertObject($resource, 'data');

        $type = $resource->string('type', null);
        if ($type === null || $type === '') {
            throw new SerializerException('JSON:API resource data.type is required');
        }

        $expectedType = $this->typeForEntity($entity);
        if ($type !== $expectedType) {
            throw new SerializerException(sprintf('JSON:API resource type must be "%s", "%s" given', $expectedType, $type));
        }

        $inputId = $resource->string('id', null);
        $entityId = $this->idForEntity($entity);
        if ($inputId !== null && $entityId !== null && $inputId !== $entityId) {
            throw new SerializerException(sprintf('JSON:API resource id must be "%s", "%s" given', $entityId, $inputId));
        }

        if ($resource->exists('attributes')) {
            $this->assertObject($resource->arr('attributes'), 'attributes');
        }
        if ($resource->exists('relationships')) {
            $this->assertObject($resource->arr('relationships'), 'relationships');
        }
    }

    protected function validateRelationship(string $name, object $definition, InputData $relationship): void
    {
        $this->assertObject($relationship, 'relationships.' . $name);
        if (!$relationship->exists('data')) {
            throw new SerializerException(sprintf('JSON:API relationship "%s" must contain data', $name));
        }

        if (method_exists($definition, 'isSingle') && !$definition->isSingle()) {
            foreach ($this->dataArray($relationship->arr('data', []), 'relationships.' . $name . '.data') as $identifier) {
                $this->validateResourceIdentifier($name, $definition, $identifier);
            }
            return;
        }

        if ($relationship->raw('data') === null) {
            return;
        }

        $this->validateResourceIdentifier($name, $definition, $relationship->arr('data', []));
    }

    protected function validateResourceIdentifier(string $name, object $definition, InputData $identifier): void
    {
        $this->assertObject($identifier, 'relationships.' . $name . '.data');

        $id = $identifier->string('id', null);
        $type = $identifier->string('type', null);
        if ($id === null || $id === '' || $type === null || $type === '') {
            throw new SerializerException(sprintf('JSON:API relationship "%s" data must contain id and type', $name));
        }

        if (method_exists($definition, 'getExpectedType')) {
            $expectedType = $definition->getExpectedType();
            if ($expectedType !== null && $type !== $expectedType) {
                throw new SerializerException(sprintf('JSON:API relationship "%s" type must be "%s", "%s" given', $name, $expectedType, $type));
            }
        }
    }

    protected function assertObject(InputData $input, string $path): void
    {
        $data = $input->getData();
        if ($data === null) {
            return;
        }
        if (!is_array($data)) {
            throw new SerializerException(sprintf('JSON:API %s must be an object', $path));
        }
        if ($data !== [] && array_is_list($data)) {
            throw new SerializerException(sprintf('JSON:API %s must be an object', $path));
        }
    }

    protected function validateDocument(InputData $document): void
    {
        $this->assertObject($document, 'document');
        if (!$document->exists('data')) {
            throw new SerializerException('JSON:API document data is required');
        }
        if ($document->raw('data') === null) {
            throw new SerializerException('JSON:API document data cannot be null when parsing an entity');
        }
        $this->assertObject($document->arr('data'), 'data');

        if ($document->exists('included')) {
            $this->dataArray($document->arr('included'), 'included');
        }
    }

    protected function assertKnownKeys(InputData $input, array $definitions, string $path): void
    {
        $data = $input->getData();
        foreach (array_keys((array) $data) as $key) {
            if (!array_key_exists($key, $definitions)) {
                throw new SerializerException(sprintf('Unknown JSON:API %s key "%s"', $path, $key));
            }
        }
    }

    protected function attributeDefinitions(): array
    {
        return iterator_to_array($this->serializer->defineAttributes());
    }

    protected function relationshipDefinitions(): array
    {
        return iterator_to_array($this->serializer->defineRelationships());
    }

    protected function definitionObject(mixed $definition): object
    {
        if (!is_object($definition)) {
            throw new SerializerException('Serializer definitions must be objects');
        }

        return $definition;
    }

    protected function dataArray(InputData $input, string $path): array
    {
        $data = $input->getData();
        if (!is_array($data) || !array_is_list($data)) {
            throw new SerializerException(sprintf('JSON:API %s must be an array', $path));
        }

        return array_map(fn ($item) => new InputData(is_object($item) ? (array) $item : $item), $data);
    }

    protected function typeForEntity(object $entity): string
    {
        return $this->typeForClass($entity::class);
    }

    protected function typeForClass(string $class): string
    {
        if (!class_exists($class)) {
            throw new SerializerException(sprintf('Class "%s" does not exist', $class));
        }

        return (new \ReflectionClass($class))->getShortName();
    }

    protected function idForEntity(object $entity): ?string
    {
        if (method_exists($entity, 'getId')) {
            return (string) $entity->getId();
        }
        if (property_exists($entity, 'id') && $entity->id !== null) {
            return (string) $entity->id;
        }

        return null;
    }

    protected function findEntity(array $entities, string $id): ?object
    {
        foreach ($entities as $entity) {
            if ($this->idForEntity($entity) === $id) {
                return $entity;
            }
        }

        return null;
    }

    protected function assertValid(mixed $value, Constraint|array $constraints, string $path): void
    {
        $violations = $this->validator->validate($value, $constraints);
        if (count($violations) === 0) {
            return;
        }

        throw new SerializerException(sprintf('Invalid JSON:API %s: %s', $path, $this->formatViolations($violations)));
    }

    protected function formatViolations(ConstraintViolationListInterface $violations): string
    {
        $messages = [];
        foreach ($violations as $violation) {
            $messages[] = $violation->getMessage();
        }

        return implode('; ', $messages);
    }
}
