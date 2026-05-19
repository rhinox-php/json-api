<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Tests\Example;

class TestRelatedEntity
{
    public function __construct(
        private ?int $id = null,
        private ?string $childName = null
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChildName(): ?string
    {
        return $this->childName;
    }
}
