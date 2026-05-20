<?php

declare(strict_types=1);

namespace Rhinox\JsonApi;

use Rhinox\JsonApi\Definitions\BoolDefinition;
use Rhinox\JsonApi\Definitions\DateTimeDefinition;
use Rhinox\JsonApi\Definitions\FloatDefinition;
use Rhinox\JsonApi\Definitions\IntDefinition;
use Rhinox\JsonApi\Definitions\JsonDefinition;
use Rhinox\JsonApi\Definitions\ManyDefinition;
use Rhinox\JsonApi\Definitions\SingleDefinition;
use Rhinox\JsonApi\Definitions\StringDefinition;
use Symfony\Component\Validator\Constraint;

class Define
{
    public function string(string $name, bool $required = false, Constraint|array|null $validate = null): \Generator
    {
        yield $name => new StringDefinition($name, $required, $this->constraints($validate));
    }

    public function json(string $name, bool $required = false, Constraint|array|null $validate = null): \Generator
    {
        yield $name => new JsonDefinition($name, $required, $this->constraints($validate));
    }

    public function int(string $name, bool $required = false, Constraint|array|null $validate = null): \Generator
    {
        yield $name => new IntDefinition($name, $required, $this->constraints($validate));
    }

    public function float(string $name, bool $required = false, Constraint|array|null $validate = null): \Generator
    {
        yield $name => new FloatDefinition($name, $required, $this->constraints($validate));
    }

    public function bool(string $name, bool $required = false, Constraint|array|null $validate = null): \Generator
    {
        yield $name => new BoolDefinition($name, $required, $this->constraints($validate));
    }

    public function dateTime(string $name, bool $required = false, Constraint|array|null $validate = null): \Generator
    {
        yield $name => new DateTimeDefinition($name, $required, $this->constraints($validate));
    }

    public function single(string $name, string $serializerClass, bool $required = false, ?callable $setter = null): \Generator
    {
        yield $name => new SingleDefinition($name, $serializerClass, $required, $setter);
    }

    public function belongsTo(string $name, string $serializerClass, bool $required = false, ?callable $setter = null): \Generator
    {
        yield from $this->single($name, $serializerClass, $required, $setter);
    }

    public function hasMany(string $name, string $serializerClass): \Generator
    {
        yield $name => new ManyDefinition($name, $serializerClass);
    }

    private function constraints(Constraint|array|null $validate): array
    {
        if ($validate === null) {
            return [];
        }

        return is_array($validate) ? $validate : [$validate];
    }
}
