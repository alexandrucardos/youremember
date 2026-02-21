<?php

namespace App\Repository;

use App\Domain\Model\ImageArchive\EventNotFoundException;
use App\Domain\Model\ImageArchive\ImageArchiveEntity;
use App\Domain\Model\ImageArchive\ImageArchiveRepositoryInterface;
use App\Entity\ImageArchive;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ImageArchive>
 */
class ImageArchiveRepository extends ServiceEntityRepository implements ImageArchiveRepositoryInterface
{
    private EventRepository $eventRepository;

    public function __construct(
        ManagerRegistry $registry,
        EventRepository $eventRepository,
    )
    {
        parent::__construct($registry, ImageArchive::class);
        $this->eventRepository = $eventRepository;
    }

    /**
     * @throws EventNotFoundException
     */
    public function save(ImageArchiveEntity $imageArchiveEntity): void
    {
        $event = $this->eventRepository->findOneBy(
            ['uuid' => $imageArchiveEntity->eventUuidValueObject->value]
        );

        if (!$event) {
            throw new EventNotFoundException();
        }

        $imageArchive = (new ImageArchive())
            ->setEvent($event)
            ->setEmail($imageArchiveEntity->emailValueObject->value);

        $this->getEntityManager()->persist($imageArchive);

        $this->getEntityManager()->flush();
    }
}