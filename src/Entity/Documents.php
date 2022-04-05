<?php

namespace App\Entity;

use App\Repository\DocumentsRepository;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use phpDocumentor\Reflection\Types\This;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity(repositoryClass=DocumentsRepository::class)
 @Vich\Uploadable()
 */
class Documents implements \Serializable
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $documentFileName;

    /**
     * @var File
     * @Vich\UploadableField(mapping="profile", fileNameProperty="documentFileName")
     */
    private $documentFile;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * @return string
     */
    public function getDocumentFileName(): ?string
    {
        return $this->documentFileName;
    }

    /**
     * @param string $documentFileName
     */
    public function setDocumentFileName(?string $documentFileName): void
    {
        $this->documentFileName = $documentFileName;
        $this->updatedAt = new \DateTime();
    }

    /**
     * @return File
     */
    public function getDocumentFile(): ?File
    {
        return $this->documentFile;
    }

    /**
     * @param File $documentFile
     */
    public function setDocumentFile(?File $documentFile): void
    {
        $this->documentFile = $documentFile;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     */
    public function setUpdatedAt(\DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }


    public function serialize()
    {
        return null;
    }

    public function unserialize($data)
    {
        $this->id = $data;
    }
}
