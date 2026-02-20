<?php

namespace App\Application\AddFeedback;

use App\Domain\Model\Feedback\FeedbackEntity;
use App\Domain\Model\Feedback\FeedbackRepositoryInterface;

class AddFeedbackHandler
{
    public function __construct(
        private readonly FeedbackRepositoryInterface $feedbackRepository,
    )
    {
    }

    public function __invoke(AddFeedbackCommand $command): void
    {
        $feedbackEntity = new FeedbackEntity(
            $command->uuidValueObject
        );

        $feedbackEntity->setFeedback($command->feedbackValueObject);

        $this->feedbackRepository->save($feedbackEntity);
    }
}