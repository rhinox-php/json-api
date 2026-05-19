<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Constraints;

use Symfony\Component\Validator\Constraint;

class RequiredKeys extends Constraint
{
    public string $message = 'JSON:API {{ kind }} "{{ key }}" is required';

    public function __construct(
        public array $keys,
        public string $kind,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);
    }
}
