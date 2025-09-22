<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Tests\Example;

class TestRelatedEntity
{
    public function __construct(
        public ?int $id = null,
        public ?string $childName = null
    ) {
    }
}
