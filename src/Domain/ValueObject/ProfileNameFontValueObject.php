<?php

declare(strict_types = 1);

namespace App\Domain\ValueObject;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

final class ProfileNameFontValueObject
{
    private const DEFAULT_FONT = 'elegant';

    #[Assert\NotBlank]
    #[Assert\Length(max: 50, min: 2)]
    public readonly mixed $value;

    public function __construct(mixed $value)
    {
        $this->value = $value ?? self::DEFAULT_FONT;

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
