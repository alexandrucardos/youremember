<?php

namespace App\Repository;

use App\Entity\Media;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Media>
 */
class MediaRepository extends ServiceEntityRepository
{
    public const THUMBNAIL_SUFFIX = '_thumb';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Media::class);
    }

    public function save(Media $media): void
    {
        $this->getEntityManager()->persist($media);

        $this->getEntityManager()->flush();
    }

    /**
     * @return array<string>
     */
    public function findPathsByOrderId(int $orderId): array
    {
        return $this->createQueryBuilder('m')
            ->select('COALESCE(m.thumbnail_path, m.file_path)')
            ->innerJoin('m.event', 'e')
            ->where('e.order_id = :orderId')
            ->setParameter('orderId', $orderId)
            ->getQuery()
            ->getArrayResult();
    }
}
