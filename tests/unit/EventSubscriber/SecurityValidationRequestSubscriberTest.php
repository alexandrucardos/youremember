<?php

declare(strict_types = 1);

namespace App\Tests\unit\EventSubscriber;

use App\EventSubscriber\SecurityValidationRequestSubscriber;
use App\Service\FrontendTokenParserService;
use App\ValueObject\UserRole;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class SecurityValidationRequestSubscriberTest extends TestCase
{
    private SecurityValidationRequestSubscriber $subscriber;
    private FrontendTokenParserService $tokenParser;

    public static function validTokenDataProvider(): array
    {
        return [
            'super admin role' => [UserRole::ROLE_SUPER_ADMIN, FrontendTokenParserService::SUPER_ADMIN_EMAIL],
            'admin role' => [UserRole::ROLE_ADMIN, 'user@example.com'],
            'guest role' => [UserRole::ROLE_GUEST, null]
        ];
    }

    public function testGetSubscribedEventsReturnsKernelRequestEvent(): void
    {
        $events = SecurityValidationRequestSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::REQUEST, $events);
        $this->assertSame('onKernelRequest', $events[KernelEvents::REQUEST]);
    }

    public function testOnKernelRequestSkipsSubRequests(): void
    {
        $requestEvent = $this->createRequestEvent(isMainRequest: false);

        $this->tokenParser->expects($this->never())->method('validateTokenAndGetUserInfo');

        $this->subscriber->onKernelRequest($requestEvent);
    }

    public function testOnKernelRequestThrowsWhenTokenHeaderIsMissing(): void
    {
        $requestEvent = $this->createRequestEvent();

        $this->tokenParser->expects($this->never())->method('validateTokenAndGetUserInfo');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Missing token header');

        $this->subscriber->onKernelRequest($requestEvent);
    }

    /**
     * @dataProvider validTokenDataProvider
     */
    public function testOnKernelRequestSetsRoleAndEmailOnValidToken(
        UserRole $role,
        ?string $email
    ): void {
        $token = 'valid-token';
        $requestEvent = $this->createRequestEvent(token: $token);

        $this->tokenParser
            ->expects($this->once())
            ->method('validateTokenAndGetUserInfo')
            ->with($token)
            ->willReturn([$role, $email]);

        $this->subscriber->onKernelRequest($requestEvent);

        $request = $requestEvent->getRequest();
        $this->assertSame(
            $role,
            $request->attributes->get(SecurityValidationRequestSubscriber::REQUEST_ATTRIBUTE_USER_ROLE)
        );
        $this->assertSame(
            $email,
            $request->attributes->get(SecurityValidationRequestSubscriber::REQUEST_ATTRIBUTE_EMAIL)
        );
    }

    protected function setUp(): void
    {
        $this->tokenParser = $this->createMock(FrontendTokenParserService::class);

        $this->subscriber = new SecurityValidationRequestSubscriber($this->tokenParser);
    }

    private function createRequestEvent(
        bool $isMainRequest = true,
        ?string $token = null
    ): RequestEvent {
        $request = new Request();

        if ($token !== null) {
            $request->headers->set('token', $token);
        }

        $kernel = $this->createMock(HttpKernelInterface::class);
        $requestType = $isMainRequest ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::SUB_REQUEST;

        return new RequestEvent($kernel, $request, $requestType);
    }
}
