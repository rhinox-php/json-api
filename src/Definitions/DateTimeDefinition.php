<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Definitions;

use Symfony\Component\Validator\Constraints as Assert;

class DateTimeDefinition
{
    public function __construct(
        private string $name,
        private bool $required = false,
        private array $constraints = [],
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(object $entity): ?array
    {
        $getter = 'get' . ucfirst($this->name);
        $value = $entity->$getter();

        if (!$value instanceof \DateTimeInterface) {
            return null;
        }

        return [
            'date' => $value->format('Y-m-d'),
            'time' => $value->format('H:i:s'),
            'timeZone' => $value->getTimezone()->getName(),
        ];
    }

    public function setValue(object $entity, mixed $value): void
    {
        $setter = 'set' . ucfirst($this->name);
        if ($value === null || $value instanceof \DateTimeInterface) {
            $entity->$setter($value);
            return;
        }

        $value = is_object($value) ? (array) $value : $value;
        $entity->$setter(new \DateTimeImmutable(
            $value['date'] . ' ' . $value['time'],
            new \DateTimeZone($value['timeZone']),
        ));
    }

    public function getConstraints(): array
    {
        return [
            ...($this->required ? [new Assert\NotBlank()] : []),
            new Assert\AtLeastOneOf([
                new Assert\Collection(
                    fields: [
                        'date' => new Assert\Required([
                            new Assert\NotBlank(),
                            new Assert\Date(),
                        ]),
                        'time' => new Assert\Required([
                            new Assert\NotBlank(),
                            new Assert\Time(),
                        ]),
                        'timeZone' => new Assert\Required([
                            new Assert\NotBlank(),
                            new Assert\Timezone(),
                        ]),
                    ],
                    allowExtraFields: false,
                ),
                new Assert\IsNull(),
            ]),
            ...$this->constraints,
        ];
    }

    public function isRequired(): bool
    {
        return $this->required;
    }
}
