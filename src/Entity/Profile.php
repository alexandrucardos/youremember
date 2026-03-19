<?php

declare(strict_types = 1);

namespace App\Entity;

use App\Repository\ProfileRepository;
use App\ValueObject\Status;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProfileRepository::class)]
class Profile
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

    #[ORM\Column(length: 50, nullable: true)]
    private string $name_font;

    #[ORM\Column(enumType: Status::class, options: ['default' => Status::VALID])]
    private ?Status $status;

    #[ORM\ManyToOne(inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $background_image = null;

    #[ORM\Column]
    private int $max_media_count = 100;

    #[ORM\Column]
    private int $media_count = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $born_at = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $departed_at = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $obituary = null;

    /** @var Collection<int, Media> */
    #[ORM\OneToMany(mappedBy: 'event', targetEntity: Media::class, cascade: ['remove'])]
    private Collection $media;

    #[ORM\Column(insertable: false, updatable: false, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(insertable: false, updatable: false, options: [
        'default' => 'CURRENT_TIMESTAMP',
        'on update' => 'CURRENT_TIMESTAMP'
    ])]
    private ?\DateTimeImmutable $modified_at = null;

    public function __construct()
    {
        $this->status = Status::VALID;
        $this->media = new ArrayCollection();
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

    public function getMediaCount(): int
    {
        return $this->media_count;
    }

    public function setMediaCount(int $media_count): static
    {
        $this->media_count = $media_count;

        return $this;
    }

    /**
     * @return Collection<int, Media>
     */
    public function getMedia(): Collection
    {
        return $this->media;
    }

    public function addMedia(Media $media): static
    {
        if (!$this->media->contains($media)) {
            $this->media->add($media);
            $media->setEvent($this);
        }

        return $this;
    }

    public function removeMedia(Media $media): static
    {
        $this->media->removeElement($media);

        return $this;
    }

    public function getNameFont(): string
    {
        return $this->name_font;
    }

    public function setNameFont(string $name_font): self
    {
        $this->name_font = $name_font;

        return $this;
    }

    public function getBornAt(): ?\DateTimeImmutable
    {
        return $this->born_at;
    }

    public function setBornAt(?\DateTimeImmutable $born_at): self
    {
        $this->born_at = $born_at;

        return $this;
    }

    public function getDepartedAt(): ?\DateTimeImmutable
    {
        return $this->departed_at;
    }

    public function setDepartedAt(?\DateTimeImmutable $departed_at): self
    {
        $this->departed_at = $departed_at;

        return $this;
    }

    public function getObituary(): ?string
    {
        return $this->obituary;
    }

    public function setObituary(?string $obituary): self
    {
        $this->obituary = $obituary;

        return $this;
    }
}
