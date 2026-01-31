<?php

namespace App\ValueObject;

use App\Exception\Event\InvalidOrderIdException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

final class OrderIdValueObject
{
    #[Assert\NotBlank]
    #[Assert\Positive]
    public readonly int $value;

    public function __construct(mixed $value)
    {
        $intValue = filter_var($value, FILTER_VALIDATE_INT);

        if ($intValue === false) {
            throw new InvalidOrderIdException('Invalid order id.');
        }

        $this->value = $intValue;

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $violations = $validator->validate($this);

        if (count($violations) > 0) {
            $messages = [];
            foreach ($violations as $violation) {
                $messages[] = $violation->getMessage();
            }
            throw new InvalidOrderIdException(implode(' ', $messages));
        }
    }
}