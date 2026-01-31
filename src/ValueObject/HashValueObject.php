<?php

namespace App\ValueObject;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

final class HashValueObject
{
    #[Assert\Length(max: 50)]
    public readonly ?string $value;

    public function __construct(mixed $value)
    {
        if ($value === null || $value === '') {
            $this->value = null;
            return;
        }

        $this->value = (string) $value;

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $violations = $validator->validate($this);

        if (count($violations) > 0) {
            $messages = [];
            foreach ($violations as $violation) {
                $messages[] = $violation->getMessage();
            }
            throw new \InvalidArgumentException(implode(' ', $messages));
        }
    }
}