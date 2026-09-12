<?php

namespace App\Tests\Service\Lobby;

use App\Entity\LobbyWaitungUser;
use App\Repository\LobbyWaitungUserRepository;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\Lobby\CreateLobbyUserService;
use App\Service\Lobby\ToModeratorWebsocketService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CreateLobbyUserServiceTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private RoomsRepository $roomsRepository;
    private LobbyWaitungUserRepository $lobbyUserRepository;
    private ToModeratorWebsocketService $toModerator;
    private CreateLobbyUserService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->userRepository = $container->get(UserRepository::class);
        $this->roomsRepository = $container->get(RoomsRepository::class);
        $this->lobbyUserRepository = $container->get(LobbyWaitungUserRepository::class);
        $this->toModerator = $this->createMock(ToModeratorWebsocketService::class);
        $this->service = new CreateLobbyUserService(
            $this->entityManager,
            $this->toModerator,
            $container->get(ParameterBagInterface::class)
        );
    }

    public function testCreatesNewLobbyUserAndNotifiesModerator(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'test@local2.de']);
        $room = $this->roomsRepository->findOneBy(['name' => 'TestMeeting: 1']);

        self::assertNull($this->lobbyUserRepository->findOneBy(['user' => $user, 'room' => $room]));

        $this->toModerator->expects($this->once())->method('newParticipantInLobby');
        $this->toModerator->expects($this->once())->method('refreshLobby');

        $lobbyUser = $this->service->createNewLobbyUser($user, $room, 'b', true);

        self::assertNotNull($lobbyUser->getId());
        self::assertSame($user, $lobbyUser->getUser());
        self::assertSame($room, $lobbyUser->getRoom());
        self::assertSame('b', $lobbyUser->getType());
        self::assertTrue($lobbyUser->isWebsocketReady());
        self::assertFalse($lobbyUser->getCloseBrowser());
        self::assertSame('User2, Test2, test@local2.de', $lobbyUser->getShowName());
        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $lobbyUser->getUid());

        $persisted = $this->lobbyUserRepository->findOneBy(['user' => $user, 'room' => $room]);
        self::assertSame($lobbyUser->getId(), $persisted->getId());
    }

    public function testReusesExistingLobbyUserWithoutNotifyingAsNewParticipant(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'test@local2.de']);
        $room = $this->roomsRepository->findOneBy(['name' => 'This is a room with Lobby']);

        $existing = (new LobbyWaitungUser())
            ->setUser($user)
            ->setRoom($room)
            ->setUid('existing-lobby-uid')
            ->setType('a')
            ->setShowName('Already Waiting')
            ->setWebsocketReady(true)
            ->setCloseBrowser(true)
            ->setCreatedAt(new \DateTime());
        $this->entityManager->persist($existing);
        $this->entityManager->flush();
        $existingId = $existing->getId();

        $this->toModerator->expects($this->never())->method('newParticipantInLobby');
        $this->toModerator->expects($this->once())->method('refreshLobby');

        $lobbyUser = $this->service->createNewLobbyUser($user, $room, 'b');

        self::assertSame($existingId, $lobbyUser->getId());
        self::assertSame('b', $lobbyUser->getType());
        self::assertFalse($lobbyUser->getCloseBrowser());
        self::assertSame('existing-lobby-uid', $lobbyUser->getUid());
        self::assertCount(1, $this->lobbyUserRepository->findBy(['user' => $user, 'room' => $room]));
    }
}
