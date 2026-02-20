<?php

namespace App\Domain\Model\ImageArchive;

interface ImageArchiveRepositoryInterface
{

    /**
     * @throws EventNotFoundException
     */
    public function save(ImageArchiveEntity $imageArchiveEntity): void;
}