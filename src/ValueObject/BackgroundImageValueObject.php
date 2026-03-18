<?php

declare(strict_types = 1);

namespace App\ValueObject;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

final class BackgroundImageValueObject
{
    #[Assert\Length(max: 50)]
    #[Assert\NotBlank]
    public readonly mixed $value;

    public function __construct(mixed $value)
    {
        if ($value === null) {
            $this->value = null;
            return;
        }

        $this->value = $value;

        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();

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
