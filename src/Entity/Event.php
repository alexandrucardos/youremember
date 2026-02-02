<?php

namespace App\Entity;

use App\Repository\EventRepository;
use App\ValueObject\Status;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::GUID)]
    private ?string $uuid = null;

    #[ORM\Column(unique: true)]
    private ?int $order_id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(
        enumType: Status::class,
        options: ['default' => Status::VALID]

    )]
    private ?Status $status = null;

    #[ORM\ManyToOne(inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $background_image = null;

    #[ORM\Column]
    private int $max_media_count = 100;

    #[ORM\Column(
        insertable: false,
        updatable: false,
        options: ['default' => 'CURRENT_TIMESTAMP']
    )]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(
        insertable: false,
        updatable: false,
        options: [
            'default' => 'CURRENT_TIMESTAMP',
            'on update' => 'CURRENT_TIMESTAMP'
        ])]
    private ?\DateTimeImmutable $modified_at = null;

    public function __construct()
    {
        $this->status = Status::VALID;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getOrderId(): ?int
    {
        return $this->order_id;
    }

    public function setOrderId(int $order_id): static
    {
        $this->order_id = $order_id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function setStatus(Status $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getModifiedAt(): ?\DateTimeImmutable
    {
        return $this->modified_at;
    }

    public function setModifiedAt(\DateTimeImmutable $modified_at): static
    {
        $this->modified_at = $modified_at;

        return $this;
    }

    public function getBackgroundImage(): ?string
    {
        return $this->background_image;
    }

    public function setBackgroundImage(?string $background_image): static
    {
        $this->background_image = $background_image;

        return $this;
    }

    public function getMaxMediaCount(): int
    {
        return $this->max_media_count;
    }

    public function setMaxMediaCount(int $max_media_count): static
    {
        $this->max_media_count = $max_media_count;

        return $this;
    }
}
