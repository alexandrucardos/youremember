<?php

namespace App\Domain\Model\Feedback;

use App\Domain\ValueObject\FeedbackValueObject;
use App\ValueObject\UuidValueObject;

class FeedbackEntity
{
    public function __construct(
        public readonly UuidValueObject     $eventUuidValueObject,
        public readonly FeedbackValueObject $feedbackValueObject,
    )
    {
    }
}