<?php

namespace App\Tests\ConferenceMapper;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Repository\CallerRoomRepository;
use App\Service\api\ConferenceMapperService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ConferenceMapperCallerIdTest extends KernelTestCase
{
    public function testfindUserByCallerId(): void
    {
        $kernel = self::bootKernel();
        $confMapperService = self::getContainer()->get(ConferenceMapperService::class);
        $id = '0123456789';
        $user = $confMapperService->findNameFromCallerId($id);
        self::assertEquals('test@local.de', $user->getEmail());
    }

    public function testfindNoUserByCallerId(): void
    {
        $kernel = self::bootKernel();
        $confMapperService = self::getContainer()->get(ConferenceMapperService::class);
        $id = '98765432156';
        $user = $confMapperService->findNameFromCallerId($id);
        self::assertNull($user);
    }

    public function testfindLDAPUserByCallerId(): void
    {
        $kernel = self::bootKernel();
        $confMapperService = self::getContainer()->get(ConferenceMapperService::class);
        $id = '987654321012';
        $user = $confMapperService->findNameFromCallerId($id);
        self::assertEquals('ldapUser@local.de', $user->getEmail());
    }

    public function testfindLDAPUserByCallerIdWithZero(): void
    {
        $kernel = self::bootKernel();
        $confMapperService = self::getContainer()->get(ConferenceMapperService::class);
        $id = '00987654321012';
        $user = $confMapperService->findNameFromCallerId($id);
        self::assertEquals('ldapUser@local.de', $user->getEmail());
    }

    public function testfindLDAPUserByCallerIdWithPlus(): void
    {
        $kernel = self::bootKernel();

        $confMapperService = self::getContainer()->get(ConferenceMapperService::class);
        $id = '+987654321012';
        $user = $confMapperService->findNameFromCallerId($id);
        self::assertEquals('ldapUser@local.de', $user->getEmail());
    }

    public function testfindLDAPUserByCallerIdWithPlusAndZero(): void
    {
        $kernel = self::bootKernel();

        $confMapperService = self::getContainer()->get(ConferenceMapperService::class);
        $id = '00+987654321012';
        $user = $confMapperService->findNameFromCallerId($id);
        self::assertEquals('ldapUser@local.de', $user->getEmail());
    }

    public function testfindLDAPUserByCallerIdWithZeroAndPlus(): void
    {
        $kernel = self::bootKernel();
        $confMapperService = self::getContainer()->get(ConferenceMapperService::class);
        $id = '+00987654321012';
        $user = $confMapperService->findNameFromCallerId($id);
        self::assertEquals('ldapUser@local.de', $user->getEmail());
    }



}
