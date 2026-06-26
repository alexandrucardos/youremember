<?php

declare(strict_types = 1);

namespace App\Service\Profile;

use App\Domain\ValueObject\ProfileIdValueObject;
use App\Domain\ValueObject\ProfileNameFontValueObject;
use App\Domain\ValueObject\ProfileNameValueObject;
use App\Domain\ValueObject\ProfileTitleValueObject;
use App\Entity\Profile;
use App\Exception\Profile\NotFoundException;
use App\Repository\ProfileRepository;

final class ProfileUpdateService
{
    public function __construct(
        private readonly ProfileRepository $profileRepository
    ) {
    }

    public function updateNameForEventUuid(
        ProfileIdValueObject $eventUuid,
        ProfileNameValueObject $name,
        ProfileNameFontValueObject $nameFont
    ): Profile {
        $event = $this->profileRepository->findOneBy(['external_id' => $eventUuid->value]);

        if (!$event) {
            throw new NotFoundException('Event not found');
        }

        $event->setName($name->value)->setNameFont($nameFont->value);

        $this->profileRepository->save($event);

        return $event;
    }

    public function updateNameFontAndTitleForEventUuid(
        ProfileIdValueObject $eventUuid,
        ProfileNameValueObject $name,
        ProfileNameFontValueObject $nameFont,
        ProfileTitleValueObject $title
    ): Profile {
        $profile = $this->profileRepository->findOneBy(['external_id' => $eventUuid->value]);

        if (!$profile) {
            throw new NotFoundException('Profile not found');
        }

        $profile->setName($name->value)->setNameFont($nameFont->value)->setTitle($title->value);

        $this->profileRepository->save($profile);

        return $profile;
    }
}
