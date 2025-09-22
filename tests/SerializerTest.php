<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Tests;

use Rhinox\JsonApi\Serializer;
use PHPUnit\Framework\TestCase;
use Rhinox\JsonApi\Tests\Example\TestEntity;
use Rhinox\JsonApi\Tests\Example\TestEntitySerializer;

class SerializerTest extends TestCase
{
    public function testSerializeObject(): void
    {
        $entity = new TestEntity(2, 'Test Object');
        $serializer = new TestEntitySerializer();
        $result = $serializer->serializeSingle($entity);

        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('2', $result['data']['id']);
        $this->assertEquals('TestEntity', $result['data']['type']);
        $this->assertEquals(['name' => 'Test Object'], $result['data']['attributes']);
    }

    public function testSerializeCollection(): void
    {
        $entities = [
            new TestEntity(1, 'First'),
            new TestEntity(2, 'Second')
        ];
        $serializer = new TestEntitySerializer();
        $result = $serializer->serializeMultiple($entities);

        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertCount(2, $result['data']);
        $this->assertEquals('1', $result['data'][0]['id']);
        $this->assertEquals('2', $result['data'][1]['id']);
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

        $this->assertEquals('value', $result['meta']['custom']);
    }
}
