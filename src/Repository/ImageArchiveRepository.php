<?php

declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Feedback;
use App\Entity\ImageArchive;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ImageArchive>
 */
class ImageArchiveRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry
    )
    {
        parent::__construct($registry, ImageArchive::class);
    }

    public function save(ImageArchive $imageArchive)
    {
        $this->getEntityManager()->persist($imageArchive);

        $this->getEntityManager()->flush();
    }
}
