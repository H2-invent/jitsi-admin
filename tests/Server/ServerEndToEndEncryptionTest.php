<?php

namespace App\Tests\Server;

use App\Entity\Rooms;
use App\Entity\User;
use App\Repository\RoomsRepository;
use App\Repository\ServerRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ServerEndToEndEncryptionTest extends WebTestCase
{
    private User $moderator;
    private User $participant;
    private Rooms $rooms;
    private $client;
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->rooms = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $this->moderator = $userRepo->findOneBy(['email' => 'test@local.de']);
        $this->participant = $userRepo->findOneBy(['email' => 'test@local2.de']);
    }

    public function testDefaultRoomNoE2EnforcementModerator(): void
    {

        $serverrepo = self::getContainer()->get(ServerRepository::class);
        $server = $serverrepo->findOneBy(['url' => 'meet.jit.si']);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->rooms = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $this->client->loginUser($this->moderator);
        $crawler = $this->client->request('GET','/room/join/b/'.$this->rooms->getId());
        self::assertStringContainsString('var enforceE2Eencryption = false;',$this->client->getResponse()->getContent());
    }
    public function testDefaultRoomActiveE2EnforcementModerator(): void
    {

        $serverrepo = self::getContainer()->get(ServerRepository::class);
        $server = $serverrepo->findOneBy(['url' => 'meet.jit.si']);
        $server->setEnforceE2e(true);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $manager->persist($server);
        $manager->flush();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->rooms = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $this->rooms->getServer()->setEnforceE2e(true);
        $this->client->loginUser($this->moderator);
        $crawler = $this->client->request('GET','/room/join/b/'.$this->rooms->getId());
        self::assertStringContainsString('var enforceE2Eencryption = true;',$this->client->getResponse()->getContent());
    }
    public function testDefaultRoomNoE2EnforcementParticipant(): void
    {

        $serverrepo = self::getContainer()->get(ServerRepository::class);
        $server = $serverrepo->findOneBy(['url' => 'meet.jit.si']);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->rooms = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $this->client->loginUser($this->participant);
        $crawler = $this->client->request('GET','/room/join/b/'.$this->rooms->getId());
        self::assertStringContainsString('var enforceE2Eencryption = false;',$this->client->getResponse()->getContent());
    }
    public function testDefaultRoomActiveE2EnforcementParticipant(): void
    {

        $serverrepo = self::getContainer()->get(ServerRepository::class);
        $server = $serverrepo->findOneBy(['url' => 'meet.jit.si']);
        $server->setEnforceE2e(true);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $manager->persist($server);
        $manager->flush();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->rooms = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $this->rooms->getServer()->setEnforceE2e(true);
        $this->client->loginUser($this->participant);
        $crawler = $this->client->request('GET','/room/join/b/'.$this->rooms->getId());
        self::assertStringContainsString('var enforceE2Eencryption = true;',$this->client->getResponse()->getContent());
    }

    public function testLobbyRoomNoE2EnforcementModerator(): void
    {

        $serverrepo = self::getContainer()->get(ServerRepository::class);
        $server = $serverrepo->findOneBy(['url' => 'meet.jit.si']);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->rooms = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $this->rooms->setLobby(true);
        $this->client->loginUser($this->moderator);
        $crawler = $this->client->request('GET','/room/join/b/'.$this->rooms->getId());
        self::assertStringContainsString('lobbyCard',$this->client->getResponse()->getContent());
        self::assertStringContainsString('var enforceE2Eencryption = false;',$this->client->getResponse()->getContent());
    }
    //Hier are the rooms with the lobby
    public function testlobbyRoomActiveE2EnforcementModerator(): void
    {

        $serverrepo = self::getContainer()->get(ServerRepository::class);
        $server = $serverrepo->findOneBy(['url' => 'meet.jit.si']);
        $server->setEnforceE2e(true);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $manager->persist($server);
        $manager->flush();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->rooms = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $this->rooms->getServer()->setEnforceE2e(true);
        $this->rooms->setLobby(true);
        $this->client->loginUser($this->moderator);
        $crawler = $this->client->request('GET','/room/join/b/'.$this->rooms->getId());
        self::assertStringContainsString('lobbyCard',$this->client->getResponse()->getContent());
        self::assertStringContainsString('var enforceE2Eencryption = true;',$this->client->getResponse()->getContent());
    }
    public function testLobbyRoomNoE2EnforcementParticipant(): void
    {

        $serverrepo = self::getContainer()->get(ServerRepository::class);
        $server = $serverrepo->findOneBy(['url' => 'meet.jit.si']);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->rooms = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $this->rooms->setLobby(true);
        $this->client->loginUser($this->participant);
        $crawler = $this->client->request('GET','/room/join/b/'.$this->rooms->getId());
        self::assertStringContainsString('Bitte warten Sie. Der Moderator wurde informiert und lässt Sie eintreten.',$this->client->getResponse()->getContent());
        self::assertStringContainsString('var enforceE2Eencryption = false;',$this->client->getResponse()->getContent());
    }
    //Hier are the rooms with the lobby
    public function testlobbyRoomActiveE2EnforcementParticipant(): void
    {

        $serverrepo = self::getContainer()->get(ServerRepository::class);
        $server = $serverrepo->findOneBy(['url' => 'meet.jit.si']);
        $server->setEnforceE2e(true);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $manager->persist($server);
        $manager->flush();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->rooms = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $this->rooms->getServer()->setEnforceE2e(true);
        $this->rooms->setLobby(true);
        $this->client->loginUser($this->participant);
        $crawler = $this->client->request('GET','/room/join/b/'.$this->rooms->getId());
        self::assertStringContainsString('Bitte warten Sie. Der Moderator wurde informiert und lässt Sie eintreten.',$this->client->getResponse()->getContent());
        self::assertStringContainsString('var enforceE2Eencryption = true;',$this->client->getResponse()->getContent());
    }
    public function testPublicRoomActiveE2Enforcement(): void
    {

        $serverrepo = self::getContainer()->get(ServerRepository::class);
        $server = $serverrepo->findOneBy(['url' => 'meet.jit.si']);
        $server->setEnforceE2e(true);
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $manager->persist($server);
        $manager->flush();

        $this->client->loginUser($this->participant);
        $crawler = $this->client->request('GET','/m/thisIsATest');
        self::assertStringContainsString('var enforceE2Eencryption = true;',$this->client->getResponse()->getContent());
    }

    public function testPublicRoomDisabledE2Enforcement(): void
    {

        $serverrepo = self::getContainer()->get(ServerRepository::class);
        $server = $serverrepo->findOneBy(['url' => 'meet.jit.si']);
        $this->client->loginUser($this->participant);
        $crawler = $this->client->request('GET','/m/thisIsATest');
        self::assertStringContainsString('var enforceE2Eencryption = false;',$this->client->getResponse()->getContent());
    }
}
