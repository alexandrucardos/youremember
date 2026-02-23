<?php

namespace App\Domain\Model\Event;

use App\Domain\ValueObject\FeedbackValueObject;
use App\ValueObject\EmailValueObject;
use App\ValueObject\UuidValueObject;

class EventEntity
{
    private FeedbackValueObject $feedbackValueObject;
    private EmailValueObject $imageArchiveEmail;

    public function __construct(
        public readonly UuidValueObject $eventUuidValueObject,
    )
    {
    }

    public function setFeedbackValueObject(FeedbackValueObject $feedbackValueObject): void
    {
        $this->feedbackValueObject = $feedbackValueObject;
    }

    public function getFeedbackValueObject(): FeedbackValueObject
    {
        return $this->feedbackValueObject;
    }

    public function setImageArchiveEmail(EmailValueObject $imageArchiveEmail): void
    {
        $this->imageArchiveEmail = $imageArchiveEmail;
    }

    public function getImageArchiveEmail(): EmailValueObject
    {
        return $this->imageArchiveEmail;
    }
}