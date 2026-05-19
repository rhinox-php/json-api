<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Tests;

use PHPUnit\Framework\TestCase;
use Rhino\InputData\InputData;
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

        $this->expectException(SerializerException::class);
        $this->expectExceptionMessage('Unknown JSON:API attributes key "unknown"');

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

        $this->expectException(SerializerException::class);
        $this->expectExceptionMessage('JSON:API attribute "name" is required');

        $parser->parse($entity, new InputData([
            'data' => [
                'type' => 'TestEntity',
            ],
        ]));
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
