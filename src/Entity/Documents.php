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
    private $id;

    public function getId(): ?int
    {
        return $this->id;
    }

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $documentFileName;


    #[Vich\UploadableField(mapping: "profile", fileNameProperty: "documentFileName")]
    #[Assert\File(maxSize: "3M",maxSizeMessage: 'The file is too large ({{ size }} {{ suffix }}). Allowed maximum size is {{ limit }} {{ suffix }}',)]
    private $documentFile;
    /**
     * @var \DateTime
     */
    #[ORM\Column(type: 'datetime')]
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

    public function __serialize()
    {
        return array('id'=>$this->getId());
    }
    public function __unserialize($data)
    {
        $this->id = $data;
    }
    public function serialize()
    {
        return serialize($this->__serialize());
    }
    public function unserialize($data)
    {
        $this->id = $data;
    }
}
