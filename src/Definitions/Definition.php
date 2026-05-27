<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Definitions;

use Rhinox\JsonApi\Access\AttributeAccess;
use Symfony\Component\Validator\Constraint;

abstract class Definition
{
    public function __construct(
        private string $name,
        private AttributeAccess $access,
        private bool $required = false,
    ) {
    }

    public function castValue(mixed $value): mixed
    {
        return $value;
    }

    public function getValue(mixed &$entity): mixed
    {
        $value = $this->access->get($this, $entity);
        return $this->serializeValue($value);
    }

    protected function serializeValue(mixed $value): mixed
    {
        return $this->castValue($value);
    }

    public function setValue(mixed &$entity, mixed $value): void
    {
        $this->access->set($this, $entity, $this->parseValue($value));
    }

    protected function parseValue(mixed $value): mixed
    {
        return $this->castValue($value);
    }

    public function getConstraints(): array
    {
        return [];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getRequired(): bool
    {
        return $this->required;
    }
}
