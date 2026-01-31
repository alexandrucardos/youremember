<?php

namespace App\Tests\Service\Profile;

use App\Entity\Profile;
use App\Service\Event\EventDataService;
use App\Service\MediatorS3Service;
use PHPUnit\Framework\TestCase;

final class ProfileViewServiceTest extends TestCase
{
    public function testBuildMediaDataReturnsEmptyWhenProfileIdIsNull(): void
    {
        $profile = new Profile(); // id is null

        $mediator = $this->createMock(MediatorS3Service::class);
        $mediator->expects(self::never())->method('buildUrl');
        $mediator->expects(self::never())->method('fetchContentUrls');

        $service = new EventDataService($mediator);

        self::assertSame(
            [
                'backgroundPictureUrl' => null,
                'profilePictureUrl' => null,
                'images' => [],
            ],
            $service->fetch($profile)
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
            MediatorS3Service::FOLDER_PICTURES
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

        $service = new EventDataService($mediator);

        self::assertSame(
            [
                'backgroundPictureUrl' => 'bg-url',
                'profilePictureUrl' => 'profile-url',
                'images' => ['p1', 'p2'],
            ],
            $service->fetch($profile)
        );
    }
}

