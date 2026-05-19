<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Constraints;

use Symfony\Component\Validator\Constraint;

class JsonApiList extends Constraint
{
    public string $message = 'JSON:API {{ path }} must be an array';

    public function __construct(
        public string $path,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);
    }
}
