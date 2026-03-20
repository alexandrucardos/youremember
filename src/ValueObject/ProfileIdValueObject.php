<?php

declare(strict_types = 1);

namespace App\ValueObject;

use App\Exception\Event\InvalidProfileIdException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

final class ProfileIdValueObject
{
    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Assert\Positive]
    public readonly int $value;

    public function __construct(mixed $value)
    {
        $intValue = filter_var($value, FILTER_VALIDATE_INT);

        if ($intValue === false) {
            throw new InvalidProfileIdException('Invalid profile id.');
        }

        $this->value = $intValue;

        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();

        $violations = $validator->validate($this);

        if (count($violations) > 0) {
            $messages = [];
            foreach ($violations as $violation) {
                $messages[] = $violation->getMessage();
            }
            throw new InvalidProfileIdException(implode(' ', $messages));
        }
    }
}
