<?php

declare(strict_types = 1);

namespace App\ValueObject;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

final class UuidValueObject
{
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Uuid]
    public readonly string $value;

    public function __construct(mixed $value)
    {
        $this->value = (string) $value;

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
