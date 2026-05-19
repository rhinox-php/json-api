<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Tests\Example;

class TestEntity
{
    public function __construct(
        private ?int $id = null,
        private ?string $name = null,
        private ?TestRelatedEntity $related = null,
        private ?string $relatedId = null,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getRelated(): ?TestRelatedEntity
    {
        return $this->related;
    }

    public function setRelatedId(?string $relatedId): void
    {
        $this->relatedId = $relatedId;
    }

    public function getRelatedId(): ?string
    {
        return $this->relatedId;
    }
}
