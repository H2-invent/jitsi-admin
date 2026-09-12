<?php

namespace App\Tests\Repository;

use App\Entity\Documents;
use App\Repository\DocumentsRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DocumentsRepositoryTest extends KernelTestCase
{
    public function testIsMappedToTheDocumentsEntity(): void
    {
        self::bootKernel();
        $repository = self::getContainer()->get(DocumentsRepository::class);

        $this->assertInstanceOf(DocumentsRepository::class, $repository);
        $this->assertSame(Documents::class, $repository->getClassName());
    }
}
