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
}
