<?php

declare(strict_types=1);

namespace Rhinox\JsonApi;

class JsonObject implements \JsonSerializable, \ArrayAccess
{
    private \stdClass $data;

    public function __construct(
        iterable $data = [],
        private bool $nullWhenEmpty = false
    ) {
        $this->data = new \stdClass();
        foreach ($data as $key => $value) {
            $this->data->$key = $value;
        }
    }

    public function jsonSerialize(): mixed
    {
        if ($this->nullWhenEmpty && empty($this->data)) {
            return null;
        }
        return $this->data;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data->$offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->data->$offset ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            throw new \InvalidArgumentException('Offset cannot be null');
        }
        $this->data->$offset = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->data->$offset);
    }
}
