<?php

namespace App\Tests\Service\Profile;

use App\Entity\Profile;
use App\Repository\ProfileRepository;
use App\Service\Event\EventFetchService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ProfileFetchServiceTest extends TestCase
{
    public function testFetchByIdThrows404ForInvalidId(): void
    {
        $repo = $this->createMock(ProfileRepository::class);
        $repo->expects(self::never())->method('find');

        $service = new EventFetchService($repo);

        $this->expectException(NotFoundHttpException::class);
        $service->fetchByOrderId('nope');
    }

    public function testFetchByIdThrows404WhenProfileNotFound(): void
    {
        $repo = $this->createMock(ProfileRepository::class);
        $repo->expects(self::once())
            ->method('find')
            ->with(123)
            ->willReturn(null);

        $service = new EventFetchService($repo);

        $this->expectException(NotFoundHttpException::class);
        $service->fetchByOrderId('123');
    }

    public function testFetchByIdReturnsProfileWhenFound(): void
    {
        $profile = (new Profile())->setId(123);

        $repo = $this->createMock(ProfileRepository::class);
        $repo->expects(self::once())
            ->method('find')
            ->with(123)
            ->willReturn($profile);

        $service = new EventFetchService($repo);

        self::assertSame($profile, $service->fetchByOrderId(123));
    }
}

