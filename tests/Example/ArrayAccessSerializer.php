<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Tests\Example;

use Rhinox\JsonApi\Access\ArrayAccess;
use Rhinox\JsonApi\Define;
use Rhinox\JsonApi\Serializer;

class ArrayAccessSerializer extends Serializer
{
    protected Define $define {
        get {
            return $this->define ??= new Define(new ArrayAccess());
        }
    }

    public function defineAttributes(): iterable
    {
        yield from $this->define->string('stringTest', required: true);
        yield from $this->define->int('intTest', required: true);
        yield from $this->define->float('floatTest', required: true);
        yield from $this->define->bool('boolTest', required: true);
        yield from $this->define->dateTime('dateTimeTest', required: true);
    }

    public function getType(mixed &$entity): string
    {
        return 'TestEntity';
    }
}
