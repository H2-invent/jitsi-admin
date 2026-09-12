<?php

namespace App\Tests\Repository;

use App\Entity\KeycloakGroupsToServers;
use App\Repository\KeycloakGroupsToServersRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class KeycloakGroupsToServersRepositoryTest extends KernelTestCase
{
    public function testIsMappedToTheKeycloakGroupsToServersEntity(): void
    {
        self::bootKernel();
        $repository = self::getContainer()->get(KeycloakGroupsToServersRepository::class);

        $this->assertInstanceOf(KeycloakGroupsToServersRepository::class, $repository);
        $this->assertSame(KeycloakGroupsToServers::class, $repository->getClassName());
    }
}
