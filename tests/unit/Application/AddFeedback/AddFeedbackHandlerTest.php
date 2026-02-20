<?php

namespace App\Tests\unit\Application\AddFeedback;

use App\Application\AddFeedback\AddFeedbackCommand;
use App\Application\AddFeedback\AddFeedbackHandler;
use App\Domain\Model\Feedback\FeedbackEntity;
use App\Domain\Model\Feedback\FeedbackRepositoryInterface;
use App\Domain\ValueObject\FeedbackValueObject;
use App\ValueObject\UuidValueObject;
use PHPUnit\Framework\TestCase;

class AddFeedbackHandlerTest extends TestCase
{
    public static function validFeedbackDataProvider(): array
    {
        return [
            'short valid feedback' => [
                'uuid' => '550e8400-e29b-41d4-a716-446655440000',
                'feedback' => 'Great event!',
            ],
            'long valid feedback' => [
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'feedback' => 'This was an absolutely wonderful event with amazing pictures and great organization.',
            ],
        ];
    }

    /**
     * @dataProvider validFeedbackDataProvider
     */
    public function testInvokeSavesFeedbackEntity(string $uuid, string $feedback): void
    {
        $feedbackRepository = $this->createMock(FeedbackRepositoryInterface::class);
        $feedbackRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (FeedbackEntity $feedbackEntity) use ($uuid, $feedback) {
                return $feedbackEntity->eventUuidValueObject->value === $uuid
                    && $feedbackEntity->feedbackValueObject->value === $feedback;
            }));

        $command = new AddFeedbackCommand(
            new FeedbackValueObject($feedback),
            new UuidValueObject($uuid),
        );

        $handler = new AddFeedbackHandler($feedbackRepository);
        $handler($command);
    }
}