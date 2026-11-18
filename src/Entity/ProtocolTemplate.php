<?php

namespace App\Entity;

use App\Repository\ProtocolTemplateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Entity\File as EmbeddedFile;
use Vich\UploaderBundle\Mapping\Attribute\Uploadable;
use Vich\UploaderBundle\Mapping\Attribute\UploadableField;

#[ORM\Entity(repositoryClass: ProtocolTemplateRepository::class)]
#[Uploadable]
class ProtocolTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $header = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $footer = null;

    #[UploadableField(
        mapping: 'logo',
        fileNameProperty: 'logo.name',
        size: 'logo.size',
        mimeType: 'logo.mimeType',
        originalName: 'logo.originalName',
        dimensions: 'logo.dimensions'
    )]
    #[Assert\Image(maxSize: '2M', mimeTypes: ['image/png', 'image/jpeg', 'image/gif'])]
    private ?File $logoFile = null;

    #[ORM\Embedded(class: EmbeddedFile::class)]
    private ?EmbeddedFile $logo = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToOne(inversedBy: 'protocolTemplate', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Server $server = null;

    public function __construct()
    {
        $this->logo = new EmbeddedFile();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHeader(): ?string
    {
        return $this->header;
    }

    public function setHeader(?string $header): static
    {
        $this->header = $header;

        return $this;
    }

    public function getFooter(): ?string
    {
        return $this->footer;
    }

    public function setFooter(?string $footer): static
    {
        $this->footer = $footer;

        return $this;
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|UploadedFile|null $logoFile
     */
    public function setLogoFile(?File $logoFile = null): void
    {
        $this->logoFile = $logoFile;

        if ($logoFile !== null) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getLogoFile(): ?File
    {
        return $this->logoFile;
    }

    public function setLogo(?EmbeddedFile $logo): static
    {
        $this->logo = $logo;

        return $this;
    }

    public function getLogo(): ?EmbeddedFile
    {
        return $this->logo;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getServer(): ?Server
    {
        return $this->server;
    }

    public function setServer(Server $server): static
    {
        $this->server = $server;

        return $this;
    }

    /**
     * The uploaded File object is not serializable, so only the persisted state is kept.
     *
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        return [
            'id' => $this->id,
            'header' => $this->header,
            'footer' => $this->footer,
            'logo' => $this->logo,
            'updatedAt' => $this->updatedAt,
            'server' => $this->server,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function __unserialize(array $data): void
    {
        $this->id = $data['id'] ?? null;
        $this->header = $data['header'] ?? null;
        $this->footer = $data['footer'] ?? null;
        $this->logo = $data['logo'] ?? new EmbeddedFile();
        $this->updatedAt = $data['updatedAt'] ?? null;
        $this->server = $data['server'] ?? null;
        $this->logoFile = null;
    }
}
