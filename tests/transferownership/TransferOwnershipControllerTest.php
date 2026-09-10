<?php

namespace App\Tests\transferownership;

use App\Entity\Rooms;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TransferOwnershipControllerTest extends WebTestCase
{
    public function testNewOwnerNotMOderator(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $newOwner = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($user);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);

        $data = $this->fetchParticipants($client, $room);
        self::assertFalse($this->participantHasTransferAction($data, $newOwner->getId()));
    }

    public function testNewOwnerIsConferenceModerator(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $newOwner = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($user);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $client->request('GET', '/room/addModerator?room=' . $room->getId() . '&user=' . $newOwner->getId());
        $data = $this->fetchParticipants($client, $room);
        self::assertTrue($this->participantHasTransferAction($data, $newOwner->getId()));
    }

    public function testNewOwnerhasNoKeycloakId(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $newOwner = $userRepo->findOneBy(['email' => 'test@local3.de']);
        $client->loginUser($user);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $client->request('GET', '/room/addModerator?room=' . $room->getId() . '&user=' . $newOwner->getId());
        $data = $this->fetchParticipants($client, $room);
        self::assertFalse($this->participantHasTransferAction($data, $newOwner->getId()));
    }

    public function testNewOwnerIsMOderatorTransform(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $newOwner = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($user);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $client->request('GET', '/room/addModerator?room=' . $room->getId() . '&user=' . $newOwner->getId());
        $client->request('GET', '/room/ownership/' . $newOwner->getId() . '/' . $room->getId());
        $client->request('GET', '/room/dashboard');
        self::assertSelectorTextContains('.innerOnce', 'Konferenz erfolgreich übertragen');
    }

    public function testNewOwnerIsNotMOderatorTransform(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $newOwner = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $client->loginUser($user);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $client->request('GET', '/room/ownership/' . $newOwner->getId() . '/' . $room->getId());
        $client->request('GET', '/room/dashboard');
        self::assertSelectorTextContains('.innerOnce', 'Fehler. Die Konferenz konnte nicht übertragen werden.');
    }

    public function testNewOwnerIsNotOwnerOfRoom(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $newOwner = $userRepo->findOneBy(['email' => 'test@local3.de']);
        $client->loginUser($user);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $client->request('GET', '/room/ownership/' . $newOwner->getId() . '/' . $room->getId());
        $client->request('GET', '/room/dashboard');
        self::assertSelectorTextContains('.innerOnce', 'Fehler. Die Konferenz konnte nicht übertragen werden.');
    }

    public function testNewOwnerIsNotKeycloakUserButBAckend(): void
    {
        $client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        $newOwner = $userRepo->findOneBy(['email' => 'test@local3.de']);
        $client->loginUser($user);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $client->request('GET', '/room/addModerator?room=' . $room->getId() . '&user=' . $newOwner->getId());
        $client->request('GET', '/room/ownership/' . $newOwner->getId() . '/' . $room->getId());
        $client->request('GET', '/room/dashboard');
        self::assertSelectorTextContains('.innerOnce', 'Fehler. Die Konferenz konnte nicht übertragen werden.');
    }

    private function fetchParticipants(KernelBrowser $client, Rooms $room): array
    {
        $client->request('GET', '/room/dashboard/api/participants/' . $room->getId());
        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        return $data;
    }

    private function participantHasTransferAction(array $data, int $userId): bool
    {
        foreach ($data['participants'] as $participant) {
            if ((int) $participant['id'] !== $userId) {
                continue;
            }
            foreach ($participant['actions'] as $action) {
                if (($action['key'] ?? null) === 'transfer') {
                    return true;
                }
            }
        }
        return false;
    }
}
