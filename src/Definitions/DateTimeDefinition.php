<?php

declare(strict_types=1);

namespace Rhinox\JsonApi\Definitions;

use Symfony\Component\Validator\Constraints;

class DateTimeDefinition extends Definition
{
    public function castValue(mixed $value): ?array
    {
        if ($value instanceof \DateTimeInterface) {
            return [
                'date' => $value->format('Y-m-d'),
                'time' => $value->format('H:i:s'),
                'timeZone' => $value->getTimezone()->getName(),
            ];
        }

        return null;
    }

    protected function parseValue(mixed $value): ?\DateTimeInterface
    {
        if ($value === null) {
            return null;
        }

        // @todo should parsing always be input data?
        $value = is_object($value) ? (array) $value : $value;
        return new \DateTimeImmutable(
            $value['date'] . ' ' . $value['time'],
            new \DateTimeZone($value['timeZone']),
        );
    }

    public function getConstraints(): array
    {
        return [
            ...($this->getRequired() ? [new Constraints\NotBlank()] : []),
            new Constraints\AtLeastOneOf([
                new Constraints\Collection(
                    fields: [
                        'date' => new Constraints\Required([
                            new Constraints\NotBlank(),
                            new Constraints\Date(),
                        ]),
                        'time' => new Constraints\Required([
                            new Constraints\NotBlank(),
                            new Constraints\Time(),
                        ]),
                        'timeZone' => new Constraints\Required([
                            new Constraints\NotBlank(),
                            new Constraints\Timezone(),
                        ]),
                    ],
                    allowExtraFields: false,
                ),
                new Constraints\IsNull(),
            ]),
        ];
    }
}
