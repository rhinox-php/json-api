<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Tests;

use PHPUnit\Framework\TestCase;
use Rhino\InputData\InputData;
use Rhinox\JsonApi\Parser;
use Rhinox\JsonApi\Tests\Example\TestEntity;
use Rhinox\JsonApi\Tests\Example\TestEntitySerializer;

class ParserTest extends TestCase
{
    public function testParse(): void
    {
        $jsonApi = <<<JSON
            {
                "data": {
                    "type": "TestEntity",
                    "id": "2",
                    "attributes": {
                        "name": "Test Object"
                    }
                }
            }
        JSON;
        $body = new InputData(json_decode($jsonApi, false));

        $serializer = new TestEntitySerializer();
        $parser = new Parser($serializer);
        $entity = new TestEntity(2);
        // $constraints = $parser->getConstraints($entity);
        // if ($this->validateSerializer($serializer, $entity, $constraints)) {
            $parser->parseAttributes($entity, $body->arr('data.attributes'));
            $parser->parseRelationships($entity, $body->arr('data.relationships'));
            // $serializer->validateEntity($entity, $this->body->arr('data'));
            // $entity->saveAll();
        // }
        var_dump($entity);
    }
}
