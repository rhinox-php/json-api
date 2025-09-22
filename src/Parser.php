<?php

declare(strict_types=1);

namespace Rhinox\JsonApi;

use Rhino\InputData\InputData;

class Parser
{
    public function __construct(
        protected Serializer $serializer,
    ) {
    }

    public function parseAttributes($entity, InputData $inputAttributes): void
    {
        foreach ($this->serializer->defineAttributes() as $attributeName => $definition) {
            if ($inputAttributes->exists($attributeName)) {
                $definition->setValue($entity, $inputAttributes->string($attributeName));
            }
        }
    }

    // public function parseAttributes(ModelInterface $entity, InputData $input)
    // {
    //     foreach ($this->defineAttributes() as $attribute => $accessors) {
    //         $setter = $accessors['set'] ?? null;
    //         if ($input->exists($attribute)) {
    //             if ($setter) {
    //                 $setter($entity, $input->raw($attribute), $input);
    //             }
    //         }
    //     }
    // }

    public function parseRelationships($entity, InputData $relationships): void
    {
    }
}
