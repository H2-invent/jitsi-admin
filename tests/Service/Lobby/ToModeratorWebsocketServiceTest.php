<?php

namespace App\Tests\Service\Lobby;

use App\Entity\LobbyWaitungUser;
use App\Entity\RoomsUser;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\Lobby\DirectSendService;
use App\Service\Lobby\ToModeratorWebsocketService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;

class ToModeratorWebsocketServiceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private RoomsRepository $roomsRepository;
    private ToModeratorWebsocketService $service;

    /** @var Update[] */
    private array $updates = [];

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->userRepository = $container->get(UserRepository::class);
        $this->roomsRepository = $container->get(RoomsRepository::class);
        $this->service = $container->get(ToModeratorWebsocketService::class);

        $this->updates = [];
        $hub = new MockHub(
            'http://localhost:3000/.well-known/mercure',
            new StaticTokenProvider('test'),
            function (Update $update): string {
                $this->updates[] = $update;
                return 'id';
            }
        );
        $container->get(DirectSendService::class)->setMercurePublisher($hub);
    }

    private function createLobbyModeratorRoom(): array
    {
        $room = $this->roomsRepository->findOneBy(['name' => 'This is a room with Lobby']);
        $lobbyModerator = $this->userRepository->findOneBy(['email' => 'test@australia.de']);

        $attribute = new RoomsUser();
        $attribute->setRoom($room);
        $attribute->setUser($lobbyModerator);
        $attribute->setLobbyModerator(true);
        $this->entityManager->persist($attribute);
        $this->entityManager->flush();
        $this->entityManager->refresh($room);

        return [$room, $lobbyModerator];
    }

    private function createLobbyUser($room): LobbyWaitungUser
    {
        $user = $this->userRepository->findOneBy(['email' => 'test@local3.de']);

        return (new LobbyWaitungUser())
            ->setUser($user)
            ->setRoom($room)
            ->setUid('lobby-user-uid-123')
            ->setShowName('LobbyGast')
            ->setType('a')
            ->setCreatedAt(new \DateTime());
    }

    public function testNewParticipantInLobbyNotifiesLobbyAndModerator(): void
    {
        [$room, $lobbyModerator] = $this->createLobbyModeratorRoom();
        $lobbyUser = $this->createLobbyUser($room);

        $this->service->newParticipantInLobby($lobbyUser);

        self::assertCount(3, $this->updates);
        $expectedMessage = 'Der Teilnehmer LobbyGast ist der Lobby der Konferenz ' . $room->getName() . ' beigetreten';

        $first = json_decode($this->updates[0]->getData(), true);
        self::assertSame(['lobby_moderator/' . $room->getUidReal()], $this->updates[0]->getTopics());
        self::assertSame('notification', $first['type']);
        self::assertSame('LobbyGast ist in der Lobby', $first['title']);
        self::assertSame($expectedMessage, $first['message']);
        self::assertSame('info', $first['color']);
        self::assertSame(5000, $first['closeAfter']);
        self::assertSame('lobby-user-uid-123', $first['messageId']);

        $personalTopics = [$this->updates[1]->getTopics()[0], $this->updates[2]->getTopics()[0]];
        sort($personalTopics);
        $expectedPersonalTopics = [
            'personal/' . $lobbyModerator->getUid(),
            'personal/' . $room->getModerator()->getUid(),
        ];
        sort($expectedPersonalTopics);
        self::assertSame($expectedPersonalTopics, $personalTopics);

        foreach ([$this->updates[1], $this->updates[2]] as $update) {
            $data = json_decode($update->getData(), true);
            self::assertSame('notification', $data['type']);
            self::assertStringContainsString('/room/join/b/' . $room->getId(), $data['message']);
            self::assertStringContainsString('Zur Lobby', $data['message']);
            self::assertStringContainsString('data-roomname="' . $room->getName() . '"', $data['message']);
            self::assertSame($expectedMessage, $data['pushNotification']);
        }
    }

    public function testRefreshLobbyByRoomPublishesRefreshForRoom(): void
    {
        $room = $this->roomsRepository->findOneBy(['name' => 'This is a room with Lobby']);

        $this->service->refreshLobbyByRoom($room);

        self::assertCount(1, $this->updates);
        self::assertSame(['lobby_moderator/' . $room->getUidReal()], $this->updates[0]->getTopics());
        $data = json_decode($this->updates[0]->getData(), true);
        self::assertSame('refresh', $data['type']);
        self::assertSame('/room/lobby/moderator/a/' . $room->getUidReal() . ' #waitingUser', $data['reloadUrl']);
    }

    public function testRefreshLobbyUsesRoomOfLobbyUser(): void
    {
        $room = $this->roomsRepository->findOneBy(['name' => 'This is a room with Lobby']);
        $lobbyUser = $this->createLobbyUser($room);

        $this->service->refreshLobby($lobbyUser);

        self::assertCount(1, $this->updates);
        self::assertSame(['lobby_moderator/' . $room->getUidReal()], $this->updates[0]->getTopics());
    }

    public function testParticipantLeftLobbyCleansAllNotifications(): void
    {
        [$room, $lobbyModerator] = $this->createLobbyModeratorRoom();
        $lobbyUser = $this->createLobbyUser($room);

        $this->service->participantLeftLobby($lobbyUser);

        self::assertCount(3, $this->updates);
        $expectedTopics = [
            'personal/' . $lobbyModerator->getUid(),
            'personal/' . $room->getModerator()->getUid(),
            'lobby_moderator/' . $room->getUidReal(),
        ];
        foreach ($expectedTopics as $index => $topic) {
            self::assertSame([$topic], $this->updates[$index]->getTopics());
            $data = json_decode($this->updates[$index]->getData(), true);
            self::assertSame('cleanNotification', $data['type']);
            self::assertSame('lobby-user-uid-123', $data['messageId']);
        }
    }
}
