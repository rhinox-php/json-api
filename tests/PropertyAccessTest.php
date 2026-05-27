<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Tests;

use PHPUnit\Framework\TestCase;
use Rhino\InputData\InputData;
use Rhinox\JsonApi\Parser;
use Rhinox\JsonApi\Tests\Example\PublicPropertyEntity;
use Rhinox\JsonApi\Tests\Example\PublicPropertyEntitySerializer;

class PropertyAccessTest extends TestCase
{
    public function testSerializePublicPropertyAttributes(): void
    {
        $entity = new PublicPropertyEntity(9, 'Public Object', true);
        $serializer = new PublicPropertyEntitySerializer();

        $result = $serializer->serializeSingle($entity);

        $this->assertSame([
            'name' => 'Public Object',
            'active' => true,
        ], $result['data']['attributes']);
    }

    public function testParsesPublicPropertyAttributes(): void
    {
        $parser = new Parser(new PublicPropertyEntitySerializer());
        $entity = new PublicPropertyEntity(9);

        $parser->parse($entity, new InputData([
            'data' => [
                'type' => 'PublicPropertyEntity',
                'id' => '9',
                'attributes' => [
                    'name' => 'Public Object',
                    'active' => true,
                ],
            ],
        ]));

        $this->assertSame('Public Object', $entity->name);
        $this->assertTrue($entity->active);
    }
}
