<?php

namespace App\Entity;

use App\Repository\ProfileRepository;
use App\ValueObject\Status;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @deprecated
 */
#[ORM\Entity(repositoryClass: ProfileRepository::class)]
class Profile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $born_at = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $deceased_at = null;

    #[ORM\Column(
        enumType: Status::class,
        options: [
            'default' => Status::VALID,
        ]
    )]
    private Status $status = Status::VALID;

    #[ORM\Column(options: ['default' => 'CURRENT_TIMESTAMP'])]
    private DateTimeImmutable $created_at;

    #[ORM\Column(options: [
        'default' => 'CURRENT_TIMESTAMP',
        'on update' => 'CURRENT_TIMESTAMP'
    ])]
    private DateTimeImmutable $updated_at;

    #[ORM\ManyToOne(inversedBy: 'profiles')]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getBornAt(): ?DateTimeImmutable
    {
        return $this->born_at;
    }

    public function setBornAt(?DateTimeImmutable $born_at): static
    {
        $this->born_at = $born_at;

        return $this;
    }

    public function getDeceasedAt(): ?DateTimeImmutable
    {
        return $this->deceased_at;
    }

    public function setDeceasedAt(?DateTimeImmutable $deceased_at): static
    {
        $this->deceased_at = $deceased_at;

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

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(DateTimeImmutable $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
