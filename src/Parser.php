<?php

declare(strict_types=1);

namespace Rhinox\JsonApi;

use Rhino\InputData\InputData;
use Rhinox\JsonApi\Constraints\JsonApiObject;
use Rhinox\JsonApi\Constraints\KnownKeys;
use Rhinox\JsonApi\Constraints\RequiredKeys;
use Rhinox\JsonApi\Exception\JsonApiException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
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
        $definitions = $this->attributeDefinitions();
        $this->validateInput($inputAttributes, [
            new JsonApiObject('attributes'),
            new KnownKeys(array_keys($definitions), 'attributes'),
            new RequiredKeys($this->requiredDefinitionNames($definitions), 'attribute'),
        ], 'attributes');

        foreach ($this->serializer->defineAttributes() as $attributeName => $definition) {
            $definition = $this->definitionObject($definition);
            if (!$inputAttributes->exists($attributeName)) {
                if ($definition->isRequired()) {
                    throw $this->error(
                        sprintf('JSON:API attribute "%s" is required', $attributeName),
                        'attributes',
                    );
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
        $definitions = $this->relationshipDefinitions();
        $this->validateInput($relationships, [
            new JsonApiObject('relationships'),
            new KnownKeys(array_keys($definitions), 'relationships'),
            new RequiredKeys($this->requiredDefinitionNames($definitions), 'relationship'),
        ], 'relationships');

        foreach ($this->serializer->defineRelationships() as $relationshipName => $definition) {
            $definition = $this->definitionObject($definition);
            if (!$relationships->exists($relationshipName)) {
                if (method_exists($definition, 'isRequired') && $definition->isRequired()) {
                    throw $this->error(
                        sprintf('JSON:API relationship "%s" is required', $relationshipName),
                        'relationships',
                    );
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
            throw $this->error(sprintf('Included serializer "%s" must extend %s', $serializerClass, Serializer::class), 'included');
        }
        if (!class_exists($entityClass)) {
            throw $this->error(sprintf('Included entity class "%s" does not exist', $entityClass), 'included');
        }

        $includedParser = new self(new $serializerClass(), $this->validator);
        $includedType = $this->typeForClass($entityClass);

        $result = [];
        foreach ($relationship->arr('data') as $identifier) {
            $id = $identifier->string('id', null);
            if ($identifier->string('type', null) !== $includedType || $id === null) {
                continue;
            }

            $entity = $this->findEntity($existingEntities, $id) ?? new $entityClass();
            foreach ($included as $includedResource) {
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
        $entityId = $this->idForEntity($entity);
        $this->validateInput($resource, [
            new JsonApiObject('data'),
            new Assert\Collection(
                fields: [
                    'id' => $entityId === null
                        ? new Assert\Optional([new Assert\Type('scalar')])
                        : new Assert\Optional([new Assert\IdenticalTo((string) $entityId)]),
                    'type' => new Assert\Required([
                        new Assert\NotBlank(message: 'JSON:API resource data.type is required'),
                        new Assert\IdenticalTo(
                            $this->typeForEntity($entity),
                            message: sprintf('JSON:API resource type must be "%s", "{{ value }}" given', $this->typeForEntity($entity)),
                        ),
                    ]),
                    'attributes' => new Assert\Optional([new JsonApiObject('attributes')]),
                    'relationships' => new Assert\Optional([new JsonApiObject('relationships')]),
                ],
                allowExtraFields: true,
                allowMissingFields: true,
            ),
        ], 'data');
    }

    protected function validateRelationship(string $name, object $definition, InputData $relationship): void
    {
        $dataConstraint = method_exists($definition, 'isSingle') && !$definition->isSingle()
            ? new Assert\All([$this->resourceIdentifierConstraint($name, $definition)])
            : new Assert\AtLeastOneOf([
                new Assert\IsNull(),
                $this->resourceIdentifierConstraint($name, $definition),
            ]);

        $this->validateInput($relationship, [
            new JsonApiObject('relationships.' . $name),
            new Assert\Collection(
                fields: [
                    'data' => new Assert\Required([
                        new Assert\NotNull(message: sprintf('JSON:API relationship "%s" must contain data', $name)),
                        $dataConstraint,
                    ]),
                ],
                allowExtraFields: true,
            ),
        ], 'relationships.' . $name);

        if (!method_exists($definition, 'isSingle') || $definition->isSingle()) {
            return;
        }
    }

    protected function validateDocument(InputData $document): void
    {
        $this->validateInput($document, [
            new JsonApiObject('document'),
            new Assert\Collection(
                fields: [
                    'data' => new Assert\Required([
                        new Assert\NotNull(message: 'JSON:API document data cannot be null when parsing an entity'),
                        new JsonApiObject('data'),
                    ]),
                    'included' => new Assert\Optional([
                        new Assert\Type('array'),
                    ]),
                ],
                allowExtraFields: true,
                missingFieldsMessage: 'JSON:API document data is required',
            ),
        ], 'document');
    }

    protected function attributeDefinitions(): array
    {
        return iterator_to_array($this->serializer->defineAttributes());
    }

    protected function relationshipDefinitions(): array
    {
        return iterator_to_array($this->serializer->defineRelationships());
    }

    protected function requiredDefinitionNames(array $definitions): array
    {
        $required = [];
        foreach ($definitions as $name => $definition) {
            $definition = $this->definitionObject($definition);
            if (method_exists($definition, 'isRequired') && $definition->isRequired()) {
                $required[] = $name;
            }
        }

        return $required;
    }

    protected function definitionObject(mixed $definition): object
    {
        if (!is_object($definition)) {
            throw $this->error('Serializer definitions must be objects');
        }

        return $definition;
    }

    protected function typeForEntity(object $entity): string
    {
        return $this->typeForClass($entity::class);
    }

    protected function typeForClass(string $class): string
    {
        if (!class_exists($class)) {
            throw $this->error(sprintf('Class "%s" does not exist', $class));
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

        throw new JsonApiException($this->errorsFromViolations($violations, $path));
    }

    protected function validateInput(InputData $input, Constraint|array $constraints, string $path): void
    {
        $this->assertValid($input->getData(), $constraints, $path);
    }

    protected function resourceIdentifierConstraint(string $relationshipName, object $definition): Constraint
    {
        $expectedType = method_exists($definition, 'getExpectedType') ? $definition->getExpectedType() : null;

        return new Assert\Sequentially([
            new JsonApiObject('relationships.' . $relationshipName . '.data'),
            new Assert\Collection(
                fields: [
                    'id' => new Assert\Required([
                        new Assert\NotBlank(message: sprintf('JSON:API relationship "%s" data must contain id and type', $relationshipName)),
                        new Assert\Type('scalar'),
                    ]),
                    'type' => new Assert\Required([
                        new Assert\NotBlank(message: sprintf('JSON:API relationship "%s" data must contain id and type', $relationshipName)),
                        $expectedType === null
                            ? new Assert\Type('scalar')
                            : new Assert\IdenticalTo(
                                $expectedType,
                                message: sprintf('JSON:API relationship "%s" type must be "%s", "{{ value }}" given', $relationshipName, $expectedType),
                            ),
                    ]),
                ],
                allowExtraFields: true,
            ),
        ]);
    }

    protected function errorsFromViolations(ConstraintViolationListInterface $violations, string $path): array
    {
        $errors = [];
        foreach ($violations as $violation) {
            $pointer = $this->pointer($path, $violation->getPropertyPath());
            $errors[] = [
                'status' => '422',
                'source' => [
                    'pointer' => $pointer,
                ],
                'title' => $this->titleForPointer($pointer),
                'detail' => str_replace('""', '"', $violation->getMessage()),
            ];
        }

        return $errors;
    }

    protected function error(string $detail, string $path = 'document', string $status = '422'): JsonApiException
    {
        $pointer = $this->pointer($path);

        return new JsonApiException([
            [
                'status' => $status,
                'source' => [
                    'pointer' => $pointer,
                ],
                'title' => $this->titleForPointer($pointer),
                'detail' => $detail,
            ],
        ], $detail);
    }

    protected function pointer(string $path, string $propertyPath = ''): string
    {
        $parts = $path === 'document' ? [] : explode('.', $path);

        if ($propertyPath !== '') {
            foreach (explode('][', trim($propertyPath, '[]')) as $part) {
                if ($part !== '') {
                    $parts[] = $part;
                }
            }
        }

        if ($parts === []) {
            return '';
        }

        if ($parts[0] !== 'data' && $parts[0] !== 'included') {
            array_unshift($parts, 'data');
        }

        return '/' . implode('/', array_map($this->escapePointerPart(...), $parts));
    }

    protected function escapePointerPart(string $part): string
    {
        return str_replace(['~', '/'], ['~0', '~1'], $part);
    }

    protected function titleForPointer(string $pointer): string
    {
        if ($pointer === '/data/attributes' || str_starts_with($pointer, '/data/attributes/')) {
            return 'Invalid Attribute';
        }
        if ($pointer === '/data/relationships' || str_starts_with($pointer, '/data/relationships/')) {
            return 'Invalid Relationship';
        }
        if ($pointer === '' || str_starts_with($pointer, '/data')) {
            return 'Invalid Resource';
        }

        return 'Invalid Document';
    }
}
