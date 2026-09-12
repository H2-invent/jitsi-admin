<?php

namespace App\Tests\Repository;

use App\Entity\License;
use App\Repository\LicenseRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class LicenseRepositoryTest extends KernelTestCase
{
    public function testIsMappedToTheLicenseEntity(): void
    {
        self::bootKernel();
        $repository = self::getContainer()->get(LicenseRepository::class);

        $this->assertInstanceOf(LicenseRepository::class, $repository);
        $this->assertSame(License::class, $repository->getClassName());
    }
}
