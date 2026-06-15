<?php

declare(strict_types = 1);

namespace App\Domain\ValueObject;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProfileNameValueObject
{
    #[Assert\NotBlank(message: 'profile.name.not_blank')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'profile.name.min_length', maxMessage: 'profile.name.max_length')]
    public readonly mixed $value;

    public function __construct(mixed $value, TranslatorInterface $translator)
    {
        $this->value = $value;

        $validator = Validation::createValidatorBuilder()
            ->setTranslator($translator)
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
