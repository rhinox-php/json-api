<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Constraints;

use Symfony\Component\Validator\Constraint;

class KnownKeys extends Constraint
{
    public string $message = 'Unknown JSON:API {{ path }} key "{{ key }}"';

    public function __construct(
        public array $keys,
        public string $path,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);
    }
}
