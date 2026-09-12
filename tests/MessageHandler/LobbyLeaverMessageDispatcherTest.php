<?php

namespace App\Tests\MessageHandler;

use App\Entity\LobbyWaitungUser;
use App\Message\LobbyLeaverMessage;
use App\MessageHandler\LobbyLeaverMessageDispatcher;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\Lobby\ToModeratorWebsocketService;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class LobbyLeaverMessageDispatcherTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private ToModeratorWebsocketService $toModerator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        // The websocket service publishes to an external hub, so it is replaced by a test double.
        $this->toModerator = $this->createMock(ToModeratorWebsocketService::class);
    }

    private function createDispatcher(): LobbyLeaverMessageDispatcher
    {
        return new LobbyLeaverMessageDispatcher(new Logger('test'), $this->toModerator, $this->entityManager);
    }

    private function createLobbyUser(bool $closeBrowser): LobbyWaitungUser
    {
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 1']);

        $lobbyUser = (new LobbyWaitungUser())
            ->setUid('lobby-' . uniqid())
            ->setUser($user)
            ->setRoom($room)
            ->setCreatedAt(new \DateTime())
            ->setShowName('Tester')
            ->setType('a')
            ->setCloseBrowser($closeBrowser);
        $this->entityManager->persist($lobbyUser);
        $this->entityManager->flush();

        return $lobbyUser;
    }

    public function testUnknownUserDoesNothing(): void
    {
        $this->toModerator->expects($this->never())->method('refreshLobby');
        $this->toModerator->expects($this->never())->method('participantLeftLobby');

        $this->createDispatcher()(new LobbyLeaverMessage('does-not-exist'));

        $this->assertNull($this->entityManager->getRepository(LobbyWaitungUser::class)->findOneBy(['uid' => 'does-not-exist']));
    }

    public function testClosedBrowserUserIsRemovedAndModeratorNotified(): void
    {
        $lobbyUser = $this->createLobbyUser(true);
        $id = $lobbyUser->getId();

        $this->toModerator->expects($this->once())->method('refreshLobby')->with($lobbyUser);
        $this->toModerator->expects($this->once())->method('participantLeftLobby')->with($lobbyUser);

        $this->createDispatcher()(new LobbyLeaverMessage($lobbyUser->getUid()));

        $this->assertNull($this->entityManager->find(LobbyWaitungUser::class, $id));
    }

    public function testRefreshedBrowserUserIsKept(): void
    {
        $lobbyUser = $this->createLobbyUser(false);
        $id = $lobbyUser->getId();

        $this->toModerator->expects($this->never())->method('refreshLobby');
        $this->toModerator->expects($this->never())->method('participantLeftLobby');

        $this->createDispatcher()(new LobbyLeaverMessage($lobbyUser->getUid()));

        $this->assertNotNull($this->entityManager->find(LobbyWaitungUser::class, $id));
    }
}
