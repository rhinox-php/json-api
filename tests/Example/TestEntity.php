<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Tests\Example;

use Rhinox\JsonApi\SerializableInterface;

class TestEntity
{
    public function __construct(
        public int $id,
        public string $name
    ) {}
}
