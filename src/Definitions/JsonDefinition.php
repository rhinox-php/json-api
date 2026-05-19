<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Definitions;

use Rhino\InputData\InputData;
use Symfony\Component\Validator\Constraints as Assert;

class JsonDefinition
{
    public function __construct(
        private string $name,
        private bool $required = false,
        private array $constraints = [],
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(object $entity): ?InputData
    {
        return $entity->{$this->name}?->isEmpty() ? null : $entity->{$this->name};
    }

    public function setValue(object $entity, mixed $value): void
    {
        $entity->{$this->name} = $value instanceof InputData ? $value : new InputData($value);
    }

    public function getConstraints(): array
    {
        return [
            ...($this->required ? [new Assert\NotBlank()] : []),
            new Assert\AtLeastOneOf([
                new Assert\Type('array'),
                new Assert\Type('object'),
                new Assert\IsNull(),
            ]),
            ...$this->constraints,
        ];
    }

    public function isRequired(): bool
    {
        return $this->required;
    }
}
