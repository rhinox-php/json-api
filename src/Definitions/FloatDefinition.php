<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Definitions;

use Symfony\Component\Validator\Constraints as Assert;

class FloatDefinition
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

    public function getValue(object $entity): ?float
    {
        $getter = 'get' . ucfirst($this->name);
        return $entity->$getter();
    }

    public function setValue(object $entity, mixed $value): void
    {
        $setter = 'set' . ucfirst($this->name);
        $entity->$setter($value === null ? null : (float) $value);
    }

    public function getConstraints(): array
    {
        return [
            ...($this->required ? [new Assert\NotBlank()] : []),
            new Assert\AtLeastOneOf([
                new Assert\Type('numeric'),
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
