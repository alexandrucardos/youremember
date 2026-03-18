<?php

declare(strict_types = 1);

namespace App\Repository;

use App\Application\AddMedia\AddMediaCommand;
use App\Application\DeleteMedia\DeleteMediaCommand;
use App\Application\ListEventInformation\ProfileViewModel;
use App\Domain\Model\Profile\ProfileEntity;
use App\Domain\Model\Profile\ProfileRepositoryInterface;
use App\Domain\Model\Profile\Exception\ProfileNotFoundException;
use App\Entity\Event;
use App\Entity\Feedback;
use App\Entity\ImageArchive;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
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
}
