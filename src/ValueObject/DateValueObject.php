<?php

declare(strict_types = 1);

namespace App\ValueObject;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

final class DateValueObject
{
    #[Assert\Type(\DateTimeImmutable::class)]
    public readonly mixed $value;

    public function __construct(mixed $value)
    {
        if (is_string($value) && $value !== '') {
            try {
                $value = new \DateTimeImmutable($value);
            } catch (\Exception $exception) {
                throw new \InvalidArgumentException('Invalid date format: ' . $exception->getMessage());
            }
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
