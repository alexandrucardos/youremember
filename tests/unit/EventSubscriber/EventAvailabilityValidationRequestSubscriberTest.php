<?php

namespace App\Tests\unit\EventSubscriber;

use App\Controller\API\EventController;
use App\Controller\API\MediaController;
use App\Exception\Event\EventInvalidException;
use App\EventSubscriber\EventAvailabilityValidationRequestSubscriber;
use App\Service\Event\EventAvailabilityService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class EventAvailabilityValidationRequestSubscriberTest extends TestCase
{
    private EventAvailabilityValidationRequestSubscriber $subscriber;
    private EventAvailabilityService $availabilityService;

    protected function setUp(): void
    {
        $this->availabilityService = $this->createMock(EventAvailabilityService::class);

        $this->subscriber = new EventAvailabilityValidationRequestSubscriber(
            $this->availabilityService,
        );
    }

    public function testGetSubscribedEventsReturnsKernelRequestEvent(): void
    {
        $events = EventAvailabilityValidationRequestSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::REQUEST, $events);
        $this->assertSame('onKernelRequest', $events[KernelEvents::REQUEST]);
    }

    public function testOnKernelRequestSkipsSubRequests(): void
    {
        $requestEvent = $this->createRequestEvent(
            routeName: EventController::NAME_EVENT_CLIENT_GET,
            isMainRequest: false
        );

        $this->availabilityService
            ->expects($this->never())
            ->method('__invoke');

        $this->subscriber->onKernelRequest($requestEvent);
    }

    /**
     * @dataProvider nonMatchingRoutesDataProvider
     */
    public function testOnKernelRequestSkipsNonMatchingRoutes(string $routeName): void
    {
        $requestEvent = $this->createRequestEvent(routeName: $routeName);

        $this->availabilityService
            ->expects($this->never())
            ->method('__invoke');

        $this->subscriber->onKernelRequest($requestEvent);
    }

    /**
     * @dataProvider matchingRoutesWithOrderIdDataProvider
     */
    public function testOnKernelRequestCallsAvailabilityServiceWithOrderId(string $routeName): void
    {
        $orderId = '123';

        $requestEvent = $this->createRequestEvent(
            routeName: $routeName,
            orderId: $orderId
        );

        $this->availabilityService
            ->expects($this->once())
            ->method('__invoke')
            ->with($orderId, null)
            ->willReturn(true);

        $this->subscriber->onKernelRequest($requestEvent);
    }

    /**
     * @dataProvider matchingRoutesWithUuidDataProvider
     */
    public function testOnKernelRequestCallsAvailabilityServiceWithUuid(string $routeName): void
    {
        $uuid = 'abc-123-def';

        $requestEvent = $this->createRequestEvent(
            routeName: $routeName,
            uuid: $uuid
        );

        $this->availabilityService
            ->expects($this->once())
            ->method('__invoke')
            ->with(null, $uuid)
            ->willReturn(true);

        $this->subscriber->onKernelRequest($requestEvent);
    }

    public function testOnKernelRequestThrowsExceptionWhenEventIsInvalid(): void
    {
        $requestEvent = $this->createRequestEvent(
            routeName: EventController::NAME_EVENT_CLIENT_GET,
            orderId: '123'
        );

        $this->availabilityService
            ->expects($this->once())
            ->method('__invoke')
            ->willReturn(false);

        $this->expectException(EventInvalidException::class);
        $this->expectExceptionMessage('The event is no longer valid!');

        $this->subscriber->onKernelRequest($requestEvent);
    }

    public function testOnKernelRequestDoesNotThrowWhenEventIsValid(): void
    {
        $requestEvent = $this->createRequestEvent(
            routeName: EventController::NAME_EVENT_CLIENT_GET,
            orderId: '123'
        );

        $this->availabilityService
            ->expects($this->once())
            ->method('__invoke')
            ->willReturn(true);

        $this->subscriber->onKernelRequest($requestEvent);

        $this->assertTrue(true);
    }

    public static function nonMatchingRoutesDataProvider(): array
    {
        return [
            'random route' => ['routeName' => 'some_random_route'],
            'event create route' => ['routeName' => EventController::NAME_EVENT_CLIENT_CREATE],
            'empty route' => ['routeName' => ''],
        ];
    }

    public static function matchingRoutesWithOrderIdDataProvider(): array
    {
        return [
            'event client get' => ['routeName' => EventController::NAME_EVENT_CLIENT_GET],
            'event client update' => ['routeName' => EventController::NAME_EVENT_CLIENT_UPDATE],
            'event client page url get' => ['routeName' => EventController::NAME_EVENT_CLIENT_PAGE_URL_GET],
            'event client name get' => ['routeName' => EventController::NAME_EVENT_CLIENT_NAME_GET],
            'media client get' => ['routeName' => MediaController::NAME_MEDIA_CLIENT_GET],
            'media client add' => ['routeName' => MediaController::NAME_MEDIA_CLIENT_ADD],
            'media client background add' => ['routeName' => MediaController::NAME_MEDIA_CLIENT_BACKGROUND_ADD],
            'media client background get' => ['routeName' => MediaController::NAME_MEDIA_CLIENT_BACKGROUND_GET],
        ];
    }

    public static function matchingRoutesWithUuidDataProvider(): array
    {
        return [
            'event guest get by uuid' => ['routeName' => EventController::NAME_EVENT_GUEST_GET_BY_UUID],
            'media guest add' => ['routeName' => MediaController::NAME_MEDIA_GUEST_ADD],
        ];
    }

    private function createRequestEvent(
        string $routeName,
        bool $isMainRequest = true,
        ?string $orderId = null,
        ?string $uuid = null
    ): RequestEvent {
        $request = new Request();
        $request->attributes->set('_route', $routeName);

        if ($orderId !== null) {
            $request->attributes->set('orderId', $orderId);
        }

        if ($uuid !== null) {
            $request->attributes->set('uuid', $uuid);
        }

        $kernel = $this->createMock(HttpKernelInterface::class);
        $requestType = $isMainRequest ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::SUB_REQUEST;

        return new RequestEvent($kernel, $request, $requestType);
    }
}