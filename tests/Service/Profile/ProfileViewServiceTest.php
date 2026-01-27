<?php

namespace App\Tests\Service\Profile;

use App\Entity\Profile;
use App\Service\MediatorS3Service;
use App\Service\Profile\ProfileViewService;
use PHPUnit\Framework\TestCase;

final class ProfileViewServiceTest extends TestCase
{
    public function testBuildMediaDataReturnsEmptyWhenProfileIdIsNull(): void
    {
        $profile = new Profile(); // id is null

        $mediator = $this->createMock(MediatorS3Service::class);
        $mediator->expects(self::never())->method('buildUrl');
        $mediator->expects(self::never())->method('fetchContentUrls');

        $service = new ProfileViewService($mediator);

        self::assertSame(
            [
                'backgroundPictureUrl' => null,
                'profilePictureUrl' => null,
                'images' => [],
            ],
            $service->buildMediaData($profile)
        );
    }

    public function testBuildMediaDataBuildsUrlsAndFetchesImages(): void
    {
        $profileId = 10;
        $profile = (new Profile())->setId($profileId);

        $expectedBackgroundKey = sprintf(
            '%d/%s/%s',
            $profileId,
            MediatorS3Service::FOLDER_PROFILE,
            MediatorS3Service::PROFILE_BACKGROUND
        );
        $expectedProfilePictureKey = sprintf(
            '%d/%s/%s',
            $profileId,
            MediatorS3Service::FOLDER_PROFILE,
            MediatorS3Service::PROFILE_PICTURE
        );
        $expectedPicturesPrefix = sprintf(
            '%d/%s',
            $profileId,
            MediatorS3Service::FOLDER_IMAGES
        );

        $mediator = $this->createMock(MediatorS3Service::class);
        $mediator->expects(self::exactly(2))
            ->method('buildUrl')
            ->withAnyParameters()
            ->willReturnMap([
                [$expectedBackgroundKey, 'bg-url'],
                [$expectedProfilePictureKey, 'profile-url'],
            ]);

        $mediator->expects(self::once())
            ->method('fetchContentUrls')
            ->with($expectedPicturesPrefix)
            ->willReturn(['p1', 'p2']);

        $service = new ProfileViewService($mediator);

        self::assertSame(
            [
                'backgroundPictureUrl' => 'bg-url',
                'profilePictureUrl' => 'profile-url',
                'images' => ['p1', 'p2'],
            ],
            $service->buildMediaData($profile)
        );
    }
}

