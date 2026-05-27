<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Tests;

use PHPUnit\Framework\TestCase;
use Rhino\InputData\InputData;
use Rhinox\JsonApi\Parser;
use Rhinox\JsonApi\Serializer;

class JsonAttributeTest extends TestCase
{
    public function testSerializeJsonAttribute(): void
    {
        $entity = new class () {
            public function getId(): int
            {
                return 1;
            }

            public function getJsonTest(): array
            {
                return [
                    'foo' => 'bar',
                    'baz' => 123,
                    'nested' => [
                        'a' => 'b',
                    ],
                ];
            }
        };
        $serializer = new class () extends Serializer {
            public function defineAttributes(): iterable
            {
                yield from $this->define->json('jsonTest', required: true);
            }

            public function getType(mixed &$entity): string
            {
                return 'TestEntity';
            }
        };
        $result = $serializer->serializeSingle($entity);

        $this->assertSame('1', $result['data']['id']);
        $this->assertSame('TestEntity', $result['data']['type']);
        $this->assertSame([
            'jsonTest' => [
                'foo' => 'bar',
                'baz' => 123,
                'nested' => [
                    'a' => 'b',
                ],
            ],
        ], $result['data']['attributes']);
    }

    public function testSerializeInputDataAttribute(): void
    {
        $entity = new class () {
            public function getId(): int
            {
                return 1;
            }

            public function getJsonTest(): InputData
            {
                return new InputData([
                    'foo' => 'bar',
                    'baz' => 123,
                    'nested' => [
                        'a' => 'b',
                    ],
                ]);
            }
        };
        $serializer = new class () extends Serializer {
            public function defineAttributes(): iterable
            {
                yield from $this->define->json('jsonTest', required: true);
            }

            public function getType(mixed &$entity): string
            {
                return 'TestEntity';
            }
        };
        $result = json_decode(json_encode($serializer->serializeSingle($entity)), true);

        $this->assertSame('1', $result['data']['id']);
        $this->assertSame('TestEntity', $result['data']['type']);
        $this->assertSame([
            'jsonTest' => [
                'foo' => 'bar',
                'baz' => 123,
                'nested' => [
                    'a' => 'b',
                ],
            ],
        ], $result['data']['attributes']);
    }

    public function testParseJsonAttribute(): void
    {
        $entity = new class () {
            private array $jsonTest = [];

            public function getId(): int
            {
                return 1;
            }

            public function getJsonTest(): array
            {
                return $this->jsonTest;
            }

            public function setJsonTest(array $data): void
            {
                $this->jsonTest = $data;
            }
        };
        $serializer = new class () extends Serializer {
            public function defineAttributes(): iterable
            {
                yield from $this->define->json('jsonTest', required: true);
            }

            public function getType(mixed &$entity): string
            {
                return 'TestEntity';
            }
        };
        $parser = new Parser($serializer);

        $parser->parse($entity, new InputData([
            'data' => [
                'type' => 'TestEntity',
                'id' => '1',
                'attributes' => [
                    'jsonTest' => [
                        'foo' => 'bar',
                        'baz' => 123,
                        'nested' => [
                            'a' => 'b',
                        ],
                    ],
                ],
            ],
        ]));

        $this->assertSame([
            'foo' => 'bar',
            'baz' => 123,
            'nested' => [
                'a' => 'b',
            ],
        ], $entity->getJsonTest());
    }


    public function testParseInputDataAttribute(): void
    {
        $entity = new class () {
            private ?InputData $jsonTest = null;

            public function getId(): int
            {
                return 1;
            }

            public function getJsonTest(): ?InputData
            {
                return $this->jsonTest;
            }

            public function setJsonTest(InputData $data): void
            {
                $this->jsonTest = $data;
            }
        };
        $serializer = new class () extends Serializer {
            public function defineAttributes(): iterable
            {
                yield from $this->define->inputData('jsonTest', required: true);
            }

            public function getType(mixed &$entity): string
            {
                return 'TestEntity';
            }
        };
        $parser = new Parser($serializer);

        $parser->parse($entity, new InputData([
            'data' => [
                'type' => 'TestEntity',
                'id' => '1',
                'attributes' => [
                    'jsonTest' => [
                        'foo' => 'bar',
                        'baz' => 123,
                        'nested' => [
                            'a' => 'b',
                        ],
                    ],
                ],
            ],
        ]));

        $this->assertSame([
            'foo' => 'bar',
            'baz' => 123,
            'nested' => [
                'a' => 'b',
            ],
        ], $entity->getJsonTest()->getData());
    }
}
