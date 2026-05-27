<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Tests;

use PHPUnit\Framework\TestCase;
use Rhinox\JsonApi\Tests\Example\TestEntity;
use Rhinox\JsonApi\Tests\Example\TestEntitySerializer;
use Rhinox\JsonApi\Tests\Example\TestRelatedEntity;
use Rhinox\JsonApi\Tests\Example\TypedEntity;
use Rhinox\JsonApi\Tests\Example\TypedEntitySerializer;

// @todo test deeply nested relationships
class SerializerTest extends TestCase
{
    public function testSerializeSingle(): void
    {
        $entity = new TestEntity(
            id: 2,
            stringTest: 'Test Object',
            intTest: 42,
            floatTest: 3.14,
            boolTest: true,
            dateTimeTest: new \DateTimeImmutable('2024-01-01 12:00:00', new \DateTimeZone('Pacific/Auckland')),
        );
        $serializer = new TestEntitySerializer();
        $result = $serializer->serializeSingle($entity);

        $this->assertArrayHasKey('data', $result);
        $this->assertSame('2', $result['data']['id']);
        $this->assertSame('TestEntity', $result['data']['type']);
        $this->assertSame([
            'stringTest' => 'Test Object',
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

    public function testSerializeMultiple(): void
    {
        $entities = [
            new TestEntity(1, 'First'),
            new TestEntity(2, 'Second'),
        ];
        $serializer = new TestEntitySerializer();
        $result = $serializer->serializeMultiple($entities);

        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertCount(2, $result['data']);
        $this->assertSame('1', $result['data'][0]['id']);
        $this->assertSame('2', $result['data'][1]['id']);
    }

    public function testSerializeNull(): void
    {
        $serializer = new TestEntitySerializer();
        $result = $serializer->jsonSerialize();

        $this->assertArrayHasKey('data', $result);
        $this->assertNull($result['data']);
    }

    public function testSerializeWithMeta(): void
    {
        $entity = new TestEntity(1, 'Example');
        $meta = ['foo' => 1, 'bar' => 10];
        $serializer = new TestEntitySerializer();
        $serializer->setMeta($meta);
        $result = $serializer->serializeSingle($entity);

        $this->assertArrayHasKey('meta', $result);
        $this->assertSame(1, $result['meta']['foo']);
        $this->assertSame(10, $result['meta']['bar']);
    }

    public function testAddMeta(): void
    {
        $entity = new TestEntity(1, 'Example');
        $serializer = new TestEntitySerializer();
        $serializer->addMeta('custom', 'value');
        $result = $serializer->serializeSingle($entity);

        $this->assertSame('value', $result['meta']['custom']);
    }

    public function testSerializeRelationship(): void
    {
        $related = new TestRelatedEntity(5, 'Child');
        $entity = new TestEntity(2, 'Parent', related: $related);
        $serializer = new TestEntitySerializer();
        $serializer->setIncluded(null);
        $result = $serializer->serializeSingle($entity);

        $this->assertArrayHasKey('relationships', $result['data']);
        $this->assertArrayHasKey('related', $result['data']['relationships']);
        $this->assertArrayHasKey('data', $result['data']['relationships']['related']);
        $this->assertSame('5', $result['data']['relationships']['related']['data']['id']);
        $this->assertSame('TestRelatedEntity', $result['data']['relationships']['related']['data']['type']);
    }

    public function testSerializeTypedAttributes(): void
    {
        $entity = new TypedEntity(
            7,
            12,
            19.95,
            true,
            new \DateTimeImmutable('2026-05-20 10:30:00', new \DateTimeZone('Pacific/Auckland')),
        );
        $serializer = new TypedEntitySerializer();

        $result = $serializer->serializeSingle($entity);

        $this->assertSame([
            'quantity' => 12,
            'price' => 19.95,
            'active' => true,
            'publishedAt' => [
                'date' => '2026-05-20',
                'time' => '10:30:00',
                'timeZone' => 'Pacific/Auckland',
            ],
        ], $result['data']['attributes']);
    }
}
