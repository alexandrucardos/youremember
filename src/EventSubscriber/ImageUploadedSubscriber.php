<?php

namespace App\EventSubscriber;

use App\Entity\Event;
use App\Event\MediaUploadedEvent;
use App\Repository\EventRepository;
use App\Service\MediatorS3Service;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ImageUploadedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly MediatorS3Service $mediatorS3Service,
        private readonly EventRepository   $eventRepository,
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MediaUploadedEvent::class => 'onMediaUploaded',
        ];
    }

    public function onMediaUploaded(MediaUploadedEvent $mediaUploadedEvent): void
    {
        $orderId = $mediaUploadedEvent->orderId;

        $s3MediaCount = $this->countS3Media($orderId);

        $event = $this->eventRepository->findOneBy(['order_id' => $orderId]);

        if (!$event instanceof Event) {
            return;
        }

        if ($event->getMediaCount() !== $s3MediaCount) {
            $event->setMediaCount($s3MediaCount);
            $this->eventRepository->save($event);
        }
    }

    private function countS3Media(int $orderId): int
    {
        $prefix = sprintf('%d/', $orderId);
        $urls = $this->mediatorS3Service->fetchContentUrls($prefix);

        return count(array_filter(
            $urls,
            fn(string $url) => !str_ends_with($url, '/' . MediatorS3Service::FILE_BACKGROUND_NAME)
        ));
    }
}