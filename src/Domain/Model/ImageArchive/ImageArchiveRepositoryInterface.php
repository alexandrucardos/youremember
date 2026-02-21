<?php

namespace App\Domain\Model\ImageArchive;

interface ImageArchiveRepositoryInterface
{

    /**
     * @throws EventNotFoundBaseException
     */
    public function save(ImageArchiveEntity $imageArchiveEntity): void;
}