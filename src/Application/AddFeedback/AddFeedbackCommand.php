<?php

namespace App\Application\AddFeedback;

use App\Domain\ValueObject\FeedbackValueObject;
use App\ValueObject\UuidValueObject;

class AddFeedbackCommand
{
    public function __construct(
        public readonly FeedbackValueObject $feedbackValueObject,
        public readonly UuidValueObject     $uuidValueObject,
    )
    {
    }
}