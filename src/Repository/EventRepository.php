<?php

namespace App\Repository;

use App\Entity\Event;
use App\ValueObject\Event\EventAddValueObject;
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

    public function save(EventAddValueObject $eventAddDto, bool $flush = true): Event
    {
        $event = (new Event())
            ->setUuid($this->generateUuid())
            ->setOrderId($eventAddDto->orderId->value)
            ->setName($eventAddDto->name->value)
            ->setBackgroundImage($eventAddDto->backgroundImage->value);

        $this->getEntityManager()->persist($event);

        if ($flush) {
            $this->getEntityManager()->flush();
        }

        return $event;
    }

    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
