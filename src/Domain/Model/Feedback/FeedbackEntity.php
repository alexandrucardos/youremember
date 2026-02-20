<?php

namespace App\Domain\Model\Feedback;

use App\Domain\ValueObject\FeedbackValueObject;
use App\ValueObject\UuidValueObject;

class FeedbackEntity
{
    private UuidValueObject $uuidValueObject;
    private FeedbackValueObject $feedback;

    public function __construct(
        UuidValueObject $uuidValueObject,
    )
    {
        $this->uuidValueObject = $uuidValueObject;
    }

    public function getUuid(): UuidValueObject
    {
        return $this->uuidValueObject;
    }

    public function getFeedback(): FeedbackValueObject
    {
        return $this->feedback;
    }

    public function setFeedback(FeedbackValueObject $feedback): void
    {
        $this->feedback = $feedback;
    }
}