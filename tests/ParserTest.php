<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Tests;

use PHPUnit\Framework\TestCase;
use Rhino\InputData\InputData;
use Rhinox\JsonApi\Exception\JsonApiException;
use Rhinox\JsonApi\Exception\SerializerException;
use Rhinox\JsonApi\Parser;
use Rhinox\JsonApi\Tests\Example\TestEntity;
use Rhinox\JsonApi\Tests\Example\TestEntitySerializer;
use Rhinox\JsonApi\Tests\Example\TypedEntity;
use Rhinox\JsonApi\Tests\Example\TypedEntitySerializer;

// @todo test rejecting parsing with ID
// @todo test it allows missing optional attributes
// @todo test parsing relationships both to-one and to-many, new and existing, and with included data
class ParserTest extends TestCase
{
    public function testParseDocument(): void
    {
        $jsonApi = InputData::jsonDecode(<<<JSON
            {
                "data": {
                    "type": "TestEntity",
                    "attributes": {
                        "stringTest": "Test Object",
                        "intTest": 42,
                        "floatTest": 3.14,
                        "boolTest": true,
                        "dateTimeTest": {
                            "date": "2024-01-01",
                            "time": "12:00:00",
                            "timeZone": "Pacific/Auckland"
                        }
                    },
                    "relationships": {
                        "related": {
                            "data": {
                                "type": "TestRelatedEntity",
                                "id": "5"
                            }
                        }
                    }
                }
            }
        JSON);

        $serializer = new TestEntitySerializer();
        $parser = new Parser($serializer);
        $entity = new TestEntity();

        $parser->parse($entity, $jsonApi);

        $this->assertSame('Test Object', $entity->getStringTest());
        $this->assertSame(42, $entity->getIntTest());
        $this->assertSame(3.14, $entity->getFloatTest());
        $this->assertTrue($entity->getBoolTest());
        $this->assertSame('2024-01-01 12:00:00', $entity->getDateTimeTest()?->format('Y-m-d H:i:s'));
        $this->assertSame('Pacific/Auckland', $entity->getDateTimeTest()->getTimezone()->getName());
        $this->assertSame('5', $entity->getRelatedId());
    }

    public function testRejectsUnknownAttributes(): void
    {
        $parser = new Parser(new TestEntitySerializer());
        $entity = new TestEntity();

        try {
            $parser->parse($entity, new InputData([
                'data' => [
                    'type' => 'TestEntity',
                    'attributes' => [
                        "stringTest" => "Test Object",
                        "intTest" => 42,
                        "floatTest" => 3.14,
                        "boolTest" => true,
                        "dateTimeTest" => [
                            "date" => "2024-01-01",
                            "time" => "12:00:00",
                            "timeZone" => "Pacific/Auckland"
                        ],
                        'unknown' => 'value',
                    ],
                ],
            ]));
        } catch (JsonApiException $exception) {
            $this->assertSame([
                'errors' => [
                    [
                        'status' => '422',
                        'source' => [
                            'pointer' => '/data/attributes/unknown',
                        ],
                        'title' => 'Invalid Attribute',
                        'detail' => 'Unknown JSON:API attributes key "unknown"',
                    ],
                ],
            ], $exception->toArray());
            return;
        }

        $this->fail('Expected JSON:API exception.');
    }

    public function testReturnsMultipleJsonApiErrors(): void
    {
        $parser = new Parser(new TestEntitySerializer());
        $entity = new TestEntity();

        try {
            $parser->parse($entity, new InputData([
                'data' => [
                    'type' => 'TestEntity',
                    'id' => '2',
                    'attributes' => [
                        "intTest" => 42,
                        "floatTest" => 3.14,
                        "boolTest" => true,
                        "dateTimeTest" => [
                            "date" => "2024-01-01",
                            "time" => "12:00:00",
                            "timeZone" => "Pacific/Auckland"
                        ],
                        'unknown' => 'value',
                    ],
                ],
            ]));
        } catch (JsonApiException $exception) {
            $this->assertSame([
                [
                    'status' => '422',
                    'source' => [
                        'pointer' => '/data/attributes/unknown',
                    ],
                    'title' => 'Invalid Attribute',
                    'detail' => 'Unknown JSON:API attributes key "unknown"',
                ],
                [
                    'status' => '422',
                    'source' => [
                        'pointer' => '/data/attributes/stringTest',
                    ],
                    'title' => 'Invalid Attribute',
                    'detail' => 'This value should not be blank.',
                ],
            ], $exception->getErrors());
            $this->assertSame(['errors' => $exception->getErrors()], $exception->jsonSerialize());
            return;
        }

        $this->fail('Expected JSON:API exception.');
    }

    public function testRejectsTypeMismatch(): void
    {
        $parser = new Parser(new TestEntitySerializer());
        $entity = new TestEntity();

        $this->expectException(SerializerException::class);
        $this->expectExceptionMessage('JSON:API resource type must be "TestEntity", "OtherEntity" given');

        $parser->parse($entity, new InputData([
            'data' => [
                'type' => 'OtherEntity',
                'attributes' => [
                    'name' => 'Test Object',
                ],
            ],
        ]));
    }

    public function testRejectsMissingRequiredAttribute(): void
    {
        $parser = new Parser(new TestEntitySerializer());
        $entity = new TestEntity();

        try {
            $parser->parse($entity, new InputData([
                'data' => [
                    'type' => 'TestEntity',
                ],
            ]));
        } catch (JsonApiException $exception) {
            $this->assertSame('/data/attributes/stringTest', $exception->getErrors()[0]['source']['pointer']);
            $this->assertSame('Invalid Attribute', $exception->getErrors()[0]['title']);
            $this->assertSame('This value should not be blank.', $exception->getErrors()[0]['detail']);
            return;
        }

        $this->fail('Expected JSON:API exception.');
    }

    public function testRejectsInvalidRelationshipType(): void
    {
        $parser = new Parser(new TestEntitySerializer());
        $entity = new TestEntity();

        $this->expectException(SerializerException::class);
        $this->expectExceptionMessage('JSON:API relationship "related" type must be "TestRelatedEntity", "WrongType" given');

        $parser->parse($entity, new InputData([
            'data' => [
                'type' => 'TestEntity',
                'id' => '2',
                'attributes' => [
                    'stringTest' => 'Test Object',
                    'intTest' => 42,
                    'floatTest' => 3.14,
                    'boolTest' => true,
                    'dateTimeTest' => [
                        'date' => '2024-01-01',
                        'time' => '12:00:00',
                        'timeZone' => 'Pacific/Auckland',
                    ],
                ],
                'relationships' => [
                    'related' => [
                        'data' => [
                            'type' => 'WrongType',
                            'id' => '5',
                        ],
                    ],
                ],
            ],
        ]));
    }

    public function testParsesTypedAttributes(): void
    {
        $parser = new Parser(new TypedEntitySerializer());
        $entity = new TypedEntity(7);

        $parser->parse($entity, new InputData([
            'data' => [
                'type' => 'TypedEntity',
                'id' => '7',
                'attributes' => [
                    'quantity' => '12',
                    'price' => '19.95',
                    'active' => true,
                    'publishedAt' => [
                        'date' => '2026-05-20',
                        'time' => '10:30:00',
                        'timeZone' => 'Pacific/Auckland',
                    ],
                ],
            ],
        ]));

        $this->assertSame(12, $entity->getQuantity());
        $this->assertSame(19.95, $entity->getPrice());
        $this->assertTrue($entity->getActive());
        $this->assertSame('2026-05-20 10:30:00', $entity->getPublishedAt()?->format('Y-m-d H:i:s'));
        $this->assertSame('Pacific/Auckland', $entity->getPublishedAt()->getTimezone()->getName());
    }

    public function testRejectsInvalidTypedAttributes(): void
    {
        $parser = new Parser(new TypedEntitySerializer());
        $entity = new TypedEntity(7);

        try {
            $parser->parse($entity, new InputData([
                'data' => [
                    'type' => 'TypedEntity',
                    'id' => '7',
                    'attributes' => [
                        'quantity' => '12.5',
                        'price' => 'not numeric',
                        'active' => 'yes',
                        'publishedAt' => [
                            'date' => 'not a date',
                            'time' => '99:99:99',
                            'timeZone' => 'Nope/Nowhere',
                        ],
                    ],
                ],
            ]));
        } catch (JsonApiException $exception) {
            $this->assertSame('/data/attributes/quantity', $exception->getErrors()[0]['source']['pointer']);
            $this->assertSame('/data/attributes/price', $exception->getErrors()[1]['source']['pointer']);
            $this->assertSame('/data/attributes/active', $exception->getErrors()[2]['source']['pointer']);
            $this->assertSame('/data/attributes/publishedAt', $exception->getErrors()[3]['source']['pointer']);
            return;
        }

        $this->fail('Expected JSON:API exception.');
    }
}
