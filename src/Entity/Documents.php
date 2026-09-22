<?php

namespace App\Entity;

use App\Repository\DocumentsRepository;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use phpDocumentor\Reflection\Types\This;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DocumentsRepository::class)]
#[Vich\Uploadable()]
class Documents implements \Serializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $documentFileName = null;

    #[Vich\UploadableField(mapping: "profile", fileNameProperty: "documentFileName")]
    #[Assert\File(maxSize: "3M",maxSizeMessage: 'The file is too large ({{ size }} {{ suffix }}). Allowed maximum size is {{ limit }} {{ suffix }}',)]
    private ?File $documentFile = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function getDocumentFileName(): ?string
    {
        return $this->documentFileName;
    }

    public function setDocumentFileName(?string $documentFileName): void
    {
        $this->documentFileName = $documentFileName;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getDocumentFile(): ?File
    {
        return $this->documentFile;
    }

    public function setDocumentFile(?File $documentFile): void
    {
        $this->documentFile = $documentFile;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function __serialize()
    {
        return array('id' => $this->getId());
    }

    public function __unserialize(mixed $data)
    {
        $this->id = (int) $data;
    }

    public function serialize(): string
    {
        return serialize($this->__serialize());
    }

    public function unserialize(mixed $data): void
    {
        $this->id = (int) $data;
    }
}
