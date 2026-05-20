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

class ParserTest extends TestCase
{
    public function testParseDocument(): void
    {
        $jsonApi = InputData::jsonDecode(<<<JSON
            {
                "data": {
                    "type": "TestEntity",
                    "id": "2",
                    "attributes": {
                        "name": "Test Object"
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
        $entity = new TestEntity(2);

        $parser->parse($entity, $jsonApi);

        $this->assertSame('Test Object', $entity->getName());
        $this->assertSame('5', $entity->getRelatedId());
    }

    public function testRejectsUnknownAttributes(): void
    {
        $parser = new Parser(new TestEntitySerializer());
        $entity = new TestEntity(2);

        try {
            $parser->parse($entity, new InputData([
                'data' => [
                    'type' => 'TestEntity',
                    'id' => '2',
                    'attributes' => [
                        'name' => 'Test Object',
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
        $entity = new TestEntity(2);

        try {
            $parser->parse($entity, new InputData([
                'data' => [
                    'type' => 'TestEntity',
                    'id' => '2',
                    'attributes' => [
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
                        'pointer' => '/data/attributes',
                    ],
                    'title' => 'Invalid Attribute',
                    'detail' => 'JSON:API attribute "name" is required',
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
        $entity = new TestEntity(2);

        $this->expectException(SerializerException::class);
        $this->expectExceptionMessage('JSON:API resource type must be "TestEntity", "OtherEntity" given');

        $parser->parse($entity, new InputData([
            'data' => [
                'type' => 'OtherEntity',
                'id' => '2',
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
            $this->assertSame('/data/attributes', $exception->getErrors()[0]['source']['pointer']);
            $this->assertSame('Invalid Attribute', $exception->getErrors()[0]['title']);
            $this->assertSame('JSON:API attribute "name" is required', $exception->getErrors()[0]['detail']);
            return;
        }

        $this->fail('Expected JSON:API exception.');
    }

    public function testRejectsInvalidRelationshipType(): void
    {
        $parser = new Parser(new TestEntitySerializer());
        $entity = new TestEntity(2);

        $this->expectException(SerializerException::class);
        $this->expectExceptionMessage('JSON:API relationship "related" type must be "TestRelatedEntity", "WrongType" given');

        $parser->parse($entity, new InputData([
            'data' => [
                'type' => 'TestEntity',
                'id' => '2',
                'attributes' => [
                    'name' => 'Test Object',
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
