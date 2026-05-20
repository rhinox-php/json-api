<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Definitions;

use Symfony\Component\Validator\Constraints as Assert;

class BoolDefinition
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

    public function getValue(object $entity): ?bool
    {
        $getter = 'get' . ucfirst($this->name);
        return $entity->$getter();
    }

    public function setValue(object $entity, mixed $value): void
    {
        $setter = 'set' . ucfirst($this->name);
        $entity->$setter($value === null ? null : (bool) $value);
    }

    public function getConstraints(): array
    {
        return [
            ...($this->required ? [new Assert\NotNull()] : []),
            new Assert\AtLeastOneOf([
                new Assert\Type('bool'),
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
