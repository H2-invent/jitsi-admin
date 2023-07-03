<?php

namespace App\Tests\Lobby\Service;

use App\Entity\CallerId;
use App\Entity\CallerSession;
use App\Entity\LobbyWaitungUser;
use App\Repository\CallerSessionRepository;
use App\Repository\LobbyWaitungUserRepository;
use App\Repository\ServerRepository;
use App\Repository\UserRepository;
use App\Service\Lobby\LobbyUtils;
use App\Service\RoomGeneratorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class LobbyUtilsTest extends KernelTestCase
{
    public function testLobyUtilsWOrkflow(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $roomGen = self::getContainer()->get(RoomGeneratorService::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['username' => 'test@local.de']);
        $user2 = $userRepo->findOneBy(['username' => 'test@local2.de']);
        $user3 = $userRepo->findOneBy(['username' => 'test@local3.de']);
        $serverRepo = self::getContainer()->get(ServerRepository::class);
        $server = $serverRepo->findOneBy(['url' => 'meet.jit.si']);
        $room = $roomGen->createRoom($user, $server);
        $room->setName('kjdshfhds');
        $lobbUtils = self::getContainer()->get(LobbyUtils::class);
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $lobbyWaitingUSerRepo = self::getContainer()->get(LobbyWaitungUserRepository::class);
        $lobbyWaitinguser = new LobbyWaitungUser();
        $lobbyWaitinguser->setUser($user2)->setRoom($room)->setShowName('test 123')->setCreatedAt(new \DateTime())->setUid('lkjsdjfjdskjf')->setType('a');
        $room->addLobbyWaitungUser($lobbyWaitinguser);
        $em->persist($room);
        $em->persist($lobbyWaitinguser);
        $em->flush();

        $lobbyWaitinguser = new LobbyWaitungUser();
        $lobbyWaitinguser->setUser($user3)->setRoom($room)->setShowName('test 1231')->setCreatedAt(new \DateTime())->setUid('lkjsdjfjdskjf')->setType('a');


        $callerId = new CallerId();
        $callerId->setCreatedAt(new \DateTime())->setRoom($room)->setUser($user3)->setCallerId('testPIN');
        $em->persist($callerId);
        $em->flush();

        $callerSession = new CallerSession();
        $callerSession
            ->setCaller($callerId)
            ->setLobbyWaitingUser($lobbyWaitinguser)
            ->setAuthOk(true)
            ->setCallerId('test123')
            ->setCreatedAt(new \DateTime())
            ->setShowName('test')
            ->setSessionId('test');
        $lobbyWaitinguser->setCallerSession($callerSession);
        $room->addLobbyWaitungUser($lobbyWaitinguser);

        $em->persist($room);
        $em->persist($lobbyWaitinguser);
        $em->persist($callerSession);
        $em->flush();

        self::assertEquals(2, sizeof($lobbyWaitingUSerRepo->findBy(['room' => $room])));
        $callerSessionRepo = self::getContainer()->get(CallerSessionRepository::class);
        $callerSess = $callerSessionRepo->findCallerSessionsByRoom($room);
        self::assertEquals(1, sizeof($callerSess));

        $lobbUtils->cleanLobby($room);

        self::assertEquals(0, sizeof($lobbyWaitingUSerRepo->findBy(['room' => $room])));
        self::assertEquals(1, sizeof($callerSess));
        self::assertTrue($callerSess[0]->getForceFinish());

        $this->assertSame('test', $kernel->getEnvironment());
    }
}
