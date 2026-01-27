<?php

namespace App\Service\Profile;

use App\Entity\Profile;
use App\Service\MediatorS3Service;

class ProfileViewService
{
    public function __construct(
        private readonly MediatorS3Service $mediatorS3Service,
    ) {
    }

    /**
     * Builds all media URLs needed to render a profile view/edit page.
     *
     * @return array{
     *     backgroundPictureUrl: string|null,
     *     profilePictureUrl: string|null,
     *     images: string[]
     * }
     */
    public function buildMediaData(Profile $profile): array
    {
        $profileId = $profile->getId();

        if ($profileId === null) {
            return [
                'backgroundPictureUrl' => null,
                'profilePictureUrl' => null,
                'images' => [],
            ];
        }

        $backgroundPictureUrl = $this->mediatorS3Service->buildUrl(
            sprintf(
                '%d/%s/%s',
                $profileId,
                MediatorS3Service::FOLDER_PROFILE,
                MediatorS3Service::PROFILE_BACKGROUND
            )
        );

        $profilePictureUrl = $this->mediatorS3Service->buildUrl(
            sprintf(
                '%d/%s/%s',
                $profileId,
                MediatorS3Service::FOLDER_PROFILE,
                MediatorS3Service::PROFILE_PICTURE
            )
        );

        $picturesUrls = $this->mediatorS3Service->fetchContentUrls(
            sprintf(
                '%d/%s',
                $profileId,
                MediatorS3Service::FOLDER_IMAGES,
            )
        );

        return [
            'backgroundPictureUrl' => $backgroundPictureUrl,
            'profilePictureUrl' => $profilePictureUrl,
            'images' => $picturesUrls,
        ];
    }
}

