<?php

namespace App\Tests\Repository;

use App\Entity\LdapUserProperties;
use App\Repository\LdapUserPropertiesRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class LdapUserPropertiesRepositoryTest extends KernelTestCase
{
    public function testIsMappedToTheLdapUserPropertiesEntity(): void
    {
        self::bootKernel();
        $repository = self::getContainer()->get(LdapUserPropertiesRepository::class);

        $this->assertInstanceOf(LdapUserPropertiesRepository::class, $repository);
        $this->assertSame(LdapUserProperties::class, $repository->getClassName());
    }
}
