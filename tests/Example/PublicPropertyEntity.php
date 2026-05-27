<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Tests\Example;

class PublicPropertyEntity
{
    public function __construct(
        public ?int $id = null,
        public ?string $name = null,
        public ?bool $active = null,
    ) {
    }
}
