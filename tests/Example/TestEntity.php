<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Tests\Example;

class TestEntity
{
    public function __construct(
        private ?int $id = null,
        private ?string $stringTest = null,
        private ?int $intTest = null,
        private ?float $floatTest = null,
        private ?bool $boolTest = null,
        private ?\DateTimeImmutable $dateTimeTest = null,
        private ?TestRelatedEntity $related = null,
        private ?string $relatedId = null,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStringTest(): ?string
    {
        return $this->stringTest;
    }

    public function setStringTest(?string $stringTest): void
    {
        $this->stringTest = $stringTest;
    }

    public function getIntTest(): ?int
    {
        return $this->intTest;
    }

    public function setIntTest(?int $intTest): void
    {
        $this->intTest = $intTest;
    }

    public function getFloatTest(): ?float
    {
        return $this->floatTest;
    }

    public function setFloatTest(?float $floatTest): void
    {
        $this->floatTest = $floatTest;
    }

    public function getBoolTest(): ?bool
    {
        return $this->boolTest;
    }

    public function setBoolTest(?bool $boolTest): void
    {
        $this->boolTest = $boolTest;
    }

    public function getDateTimeTest(): ?\DateTimeImmutable
    {
        return $this->dateTimeTest;
    }

    public function setDateTimeTest(?\DateTimeImmutable $dateTimeTest): void
    {
        $this->dateTimeTest = $dateTimeTest;
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
