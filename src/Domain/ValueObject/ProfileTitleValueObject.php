<?php

declare(strict_types = 1);

namespace App\Domain\ValueObject;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProfileTitleValueObject
{
    #[Assert\Length(min: 2, max: 50, minMessage: 'profile.title.min_length', maxMessage: 'profile.title.max_length')]
    public readonly mixed $value;

    public function __construct(mixed $value, TranslatorInterface $translator)
    {
        if ($value === null || $value === '') {
            $value = $translator->trans('profile.title.default');
        }

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
