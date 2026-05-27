<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Tests;

use PHPUnit\Framework\TestCase;
use Rhino\InputData\InputData;
use Rhino\InputData\MutableInputData;
use Rhinox\JsonApi\Access\InputDataAccess;
use Rhinox\JsonApi\Define;
use Rhinox\JsonApi\Parser;
use Rhinox\JsonApi\Serializer;

class InputDataAccessTest extends TestCase
{
    public function testSerializeInputData(): void
    {
        $entity = new InputData([
            'id' => 1,
            'foo' => 'bar',
            'baz' => 123,
            'nested' => [
                'a' => 'b',
            ],
        ]);
        $serializer = new class() extends Serializer {
            protected Define $define {
                get {
                    return $this->define ??= new Define(new InputDataAccess());
                }
            }

            public function defineAttributes(): iterable
            {
                yield from $this->define->string('foo', required: true);
                yield from $this->define->int('baz', required: true);
                yield from $this->define->json('nested', required: true);
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
            'foo' => 'bar',
            'baz' => 123,
            'nested' => [
                'a' => 'b',
            ],
        ], $result['data']['attributes']);
    }

    public function testParseInputData(): void
    {
        $serializer = new class() extends Serializer {
            protected Define $define {
                get {
                    return $this->define ??= new Define(new InputDataAccess());
                }
            }

            public function defineAttributes(): iterable
            {
                yield from $this->define->string('foo', required: true);
                yield from $this->define->int('baz', required: true);
                yield from $this->define->json('nested', required: true);
            }

            public function getType(mixed &$entity): string
            {
                return 'TestEntity';
            }
        };
        $parser = new Parser($serializer);
        $entity = new MutableInputData();
        $parser->parse($entity, new InputData([
            'data' => [
                'type' => 'TestEntity',
                'attributes' => [
                    'foo' => 'bar',
                    'baz' => 123,
                    'nested' => [
                        'a' => 'b',
                    ],
                ],
            ],
        ]));

        $this->assertSame('bar', $entity->string('foo'));
        $this->assertSame(123, $entity->int('baz'));
        $this->assertSame([
            'a' => 'b',
        ], $entity->arr('nested')->getData());
    }
}
