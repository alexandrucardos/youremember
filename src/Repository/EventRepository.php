<?php

namespace App\Repository;

use App\Domain\Model\Event\EventEntity;
use App\Domain\Model\Event\EventRepositoryInterface;
use App\Domain\Model\Event\Exception\EventNotFoundException;
use App\Entity\Event;
use App\Entity\Feedback;
use App\Entity\ImageArchive;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository implements EventRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    public function save(Event $event): void
    {
        $this->getEntityManager()->persist($event);

        $this->getEntityManager()->flush();
    }

    /**
     * @throws EventNotFoundException()
     */
    public function saveFeedbackForEvent(EventEntity $eventEntity): void
    {
        $event = $this->getEntityManager()->getRepository(Event::class)
            ->findOneBy(
                [
                    'uuid' => $eventEntity->eventUuidValueObject->value
                ]
            );

        if (!$event) {
            throw new EventNotFoundException();
        }

        $feedback = (new Feedback())
            ->setEvent($event)
            ->setFeedback($eventEntity->getFeedbackValueObject()->value);

        $this->getEntityManager()->persist($feedback);

        $this->getEntityManager()->flush();
    }

    /**
     * @throws EventNotFoundException()
     */
    public function saveArchiveEmailForEvent(EventEntity $eventEntity): void
    {
        $event = $this->getEntityManager()->getRepository(Event::class)
            ->findOneBy(
                [
                    'uuid' => $eventEntity->eventUuidValueObject->value
                ]
            );

        if (!$event) {
            throw new EventNotFoundException();
        }

        $imageArchive = (new ImageArchive())
            ->setEvent($event)
            ->setEmail($eventEntity->getImageArchiveEmail()->value);

        $this->getEntityManager()->persist($imageArchive);

        $this->getEntityManager()->flush();
    }
}
