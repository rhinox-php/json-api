<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Tests;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Rhino\InputData\InputData;
use Rhinox\JsonApi\Parser;
use Rhinox\JsonApi\Tests\Example\ArrayAccessSerializer;

// @todo test relationships
// @todo test passing an id to parse throws an error
class ArrayAccessTest extends TestCase
{
    public function testArrayAccessSerialize(): void
    {
        $serializer = new ArrayAccessSerializer();

        $entity = [
            'id' => '2',
            'type' => 'TestEntity',
            'stringTest' => 'Test String',
            'intTest' => 42,
            'floatTest' => 3.14,
            'boolTest' => true,
            'dateTimeTest' => new DateTimeImmutable('2024-01-01 12:00:00', new DateTimeZone('Pacific/Auckland')),
        ];
        $serializer = new ArrayAccessSerializer();
        $result = $serializer->serializeSingle($entity);

        $this->assertArrayHasKey('data', $result);
        $this->assertSame('2', $result['data']['id']);
        $this->assertSame('TestEntity', $result['data']['type']);
        $this->assertSame([
            'stringTest' => 'Test String',
            'intTest' => 42,
            'floatTest' => 3.14,
            'boolTest' => true,
            'dateTimeTest' => [
                'date' => '2024-01-01',
                'time' => '12:00:00',
                'timeZone' => 'Pacific/Auckland',
            ],
        ], $result['data']['attributes']);
    }

    public function testArrayAccessParse(): void
    {
        $jsonApi = InputData::jsonDecode(<<<JSON
            {
                "data": {
                    "type": "TestEntity",
                    "attributes": {
                        "stringTest": "Test String",
                        "intTest": 42,
                        "floatTest": 3.14,
                        "boolTest": true,
                        "dateTimeTest": {
                            "date": "2024-01-01",
                            "time": "12:00:00",
                            "timeZone": "Pacific/Auckland"
                        }
                    }
                }
            }
        JSON);

        $serializer = new ArrayAccessSerializer();
        $parser = new Parser($serializer);

        $entity = [];
        $parser->parse($entity, $jsonApi);
        $this->assertSame('Test String', $entity['stringTest']);
        $this->assertSame(42, $entity['intTest']);
        $this->assertSame(3.14, $entity['floatTest']);
        $this->assertSame(true, $entity['boolTest']);
        $this->assertInstanceOf(DateTimeImmutable::class, $entity['dateTimeTest']);
        $this->assertSame('2024-01-01 12:00:00', $entity['dateTimeTest']->format('Y-m-d H:i:s'));
        $this->assertSame('Pacific/Auckland', $entity['dateTimeTest']->getTimezone()->getName());
    }
}
