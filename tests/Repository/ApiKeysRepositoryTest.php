<?php

namespace App\Tests\Repository;

use App\Entity\ApiKeys;
use App\Repository\ApiKeysRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ApiKeysRepositoryTest extends KernelTestCase
{
    public function testIsMappedToTheApiKeysEntity(): void
    {
        self::bootKernel();
        $repository = self::getContainer()->get(ApiKeysRepository::class);

        $this->assertInstanceOf(ApiKeysRepository::class, $repository);
        $this->assertSame(ApiKeys::class, $repository->getClassName());
    }
}
