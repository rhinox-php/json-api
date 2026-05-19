<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Exception;

class JsonApiException extends SerializerException implements \JsonSerializable
{
    public function __construct(
        private array $errors,
        ?string $message = null,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message ?? $this->defaultMessage($errors), $code, $previous);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function toArray(): array
    {
        return [
            'errors' => $this->errors,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private function defaultMessage(array $errors): string
    {
        return implode('; ', array_map(
            fn (array $error): string => (string) ($error['detail'] ?? $error['title'] ?? 'JSON:API error'),
            $errors,
        ));
    }
}
