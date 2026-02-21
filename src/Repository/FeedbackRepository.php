<?php

namespace App\Repository;

use App\Domain\Model\Feedback\EventNotFoundException;
use App\Domain\Model\Feedback\FeedbackEntity;
use App\Domain\Model\Feedback\FeedbackRepositoryInterface;
use App\Entity\Feedback;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Feedback>
 */
class FeedbackRepository extends ServiceEntityRepository implements FeedbackRepositoryInterface
{
    private \App\Repository\EventRepository $eventRepository;

    public function __construct(
        ManagerRegistry $registry,
        EventRepository $eventRepository,
    )
    {
        parent::__construct($registry, Feedback::class);
        $this->eventRepository = $eventRepository;
    }

    /**
     * @throws EventNotFoundException
     */
    public function save(FeedbackEntity $feedbackEntity): void
    {
        $event = $this->eventRepository->findOneBy(
            [
                'uuid' => $feedbackEntity->eventUuidValueObject->value
            ]
        );

        if (!$event) {
            throw new EventNotFoundException();
        }

        $feedback = (new Feedback())->setEvent($event)
            ->setFeedback($feedbackEntity->feedbackValueObject->value);

        $this->getEntityManager()->persist($feedback);

        $this->getEntityManager()->flush();
    }
}