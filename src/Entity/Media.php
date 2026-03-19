<?php

declare(strict_types = 1);

namespace App\Entity;

use App\Repository\MediaRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MediaRepository::class)]
#[ORM\Index(name: 'idx_media_file_path', columns: ['file_path'])]
#[ORM\Index(name: 'idx_media_thumbnail_path', columns: ['thumbnail_path'])]
class Media
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'media')]
    #[ORM\JoinColumn(nullable: false)]
    private Profile $event;

    #[ORM\Column(length: 255)]
    private string $file_path;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $thumbnail_path = null;

    #[ORM\Column(length: 50)]
    private string $uploader_hash;

    #[ORM\Column(length: 20)]
    private string $file_type;

    #[ORM\Column]
    private int $file_size;

    #[ORM\Column(length: 255)]
    private string $original_filename;

    #[ORM\Column(options: ['default' => 0])]
    private bool $is_downloaded = false;

    #[ORM\Column(insertable: false, updatable: false, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(insertable: false, updatable: false, options: [
        'default' => 'CURRENT_TIMESTAMP',
        'on update' => 'CURRENT_TIMESTAMP'
    ])]
    private ?\DateTimeImmutable $modified_at = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deleted_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvent(): Profile
    {
        return $this->event;
    }

    public function setEvent(Profile $event): static
    {
        $this->event = $event;

        return $this;
    }

    public function getFilePath(): string
    {
        return $this->file_path;
    }

    public function setFilePath(string $file_path): static
    {
        $this->file_path = $file_path;

        return $this;
    }

    public function getThumbnailPath(): ?string
    {
        return $this->thumbnail_path;
    }

    public function setThumbnailPath(?string $thumbnail_path): static
    {
        $this->thumbnail_path = $thumbnail_path;

        return $this;
    }

    public function getUploaderHash(): string
    {
        return $this->uploader_hash;
    }

    public function setUploaderHash(string $uploader_hash): static
    {
        $this->uploader_hash = $uploader_hash;

        return $this;
    }

    public function getFileType(): ?string
    {
        return $this->file_type;
    }

    public function setFileType(string $file_type): static
    {
        $this->file_type = $file_type;

        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->file_size;
    }

    public function setFileSize(int $file_size): static
    {
        $this->file_size = $file_size;

        return $this;
    }

    public function getOriginalFilename(): ?string
    {
        return $this->original_filename;
    }

    public function setOriginalFilename(?string $original_filename): static
    {
        $this->original_filename = $original_filename;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getModifiedAt(): \DateTimeImmutable
    {
        return $this->modified_at;
    }

    public function setModifiedAt(\DateTimeImmutable $modified_at): static
    {
        $this->modified_at = $modified_at;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deleted_at;
    }

    public function setDeletedAt(?\DateTimeImmutable $deleted_at): static
    {
        $this->deleted_at = $deleted_at;

        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->deleted_at !== null;
    }

    public function isDownloaded(): bool
    {
        return $this->is_downloaded;
    }

    public function setIsDownloaded(bool $is_downloaded): static
    {
        $this->is_downloaded = $is_downloaded;

        return $this;
    }
}
