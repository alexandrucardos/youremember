<?php

namespace App\Domain\Model\Feedback;

interface FeedbackRepositoryInterface
{
    /**
     * @throws EventNotFoundException
     */
    public function save(FeedbackEntity $feedbackEntity): void;
}