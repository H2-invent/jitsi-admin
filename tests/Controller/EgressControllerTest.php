<?php

namespace App\Tests\Controller;

use App\Entity\Recording;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\livekit\EgressService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EgressControllerTest extends WebTestCase
{
    public function testIndexWithoutRoomReturnsError(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request('GET', '/room/start/egress/unknown-room-uid/composite');

        $this->assertResponseIsSuccessful();
        $this->assertSame(['error' => true], json_decode($client->getResponse()->getContent(), true));
    }

    public function testIndexOnNonLiveKitServerReturnsError(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 1']);
        $room->getServer()->setLiveKitServer(false);
        self::getContainer()->get(EntityManagerInterface::class)->flush();
        $client->loginUser($user);

        $client->request('GET', '/room/start/egress/' . $room->getUidReal() . '/composite');

        $this->assertResponseIsSuccessful();
        $this->assertSame(['error' => true], json_decode($client->getResponse()->getContent(), true));
    }

    public function testIndexRejectsNonModerator(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local4.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 1']);
        $room->getServer()->setLiveKitServer(true);
        self::getContainer()->get(EntityManagerInterface::class)->flush();
        $client->loginUser($user);

        $client->request('GET', '/room/start/egress/' . $room->getUidReal() . '/composite');

        $this->assertResponseIsSuccessful();
        $this->assertSame(['error' => true], json_decode($client->getResponse()->getContent(), true));
    }

    public function testIndexStartsEgressForModerator(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 1']);
        $room->getServer()->setLiveKitServer(true);
        self::getContainer()->get(EntityManagerInterface::class)->flush();

        $egressService = $this->createMock(EgressService::class);
        $egressService->expects($this->once())->method('startEgress')->willReturn(['error' => false, 'recordingId' => 'egress-123']);
        self::getContainer()->set(EgressService::class, $egressService);

        $client->loginUser($user);
        $client->request('GET', '/room/start/egress/' . $room->getUidReal() . '/composite');

        $this->assertResponseIsSuccessful();
        $this->assertSame(['error' => false, 'recordingId' => 'egress-123'], json_decode($client->getResponse()->getContent(), true));
    }

    public function testStopWithoutRecordingReturnsNotFound(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request('GET', '/room/stop/egress/unknown-recording-id');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testStopRejectsForeignKey(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $foreignUser = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local2.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 1']);
        $recording = $this->createRecording($room, $foreignUser, 'recording-foreign');
        $client->loginUser($user);

        $client->request('GET', '/room/stop/egress/' . $recording->getRecordingId());

        $this->assertResponseStatusCodeSame(404);
    }

    public function testStopStopsEgressForOwner(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 1']);
        $recording = $this->createRecording($room, $user, 'recording-owner');

        $egressService = $this->createMock(EgressService::class);
        $egressService->expects($this->once())->method('stopEgress')->willReturn(['error' => false]);
        self::getContainer()->set(EgressService::class, $egressService);

        $client->loginUser($user);
        $client->request('GET', '/room/stop/egress/' . $recording->getRecordingId());

        $this->assertResponseIsSuccessful();
        $this->assertSame(['error' => false], json_decode($client->getResponse()->getContent(), true));
    }

    private function createRecording($room, $user, string $recordingId): Recording
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $recording = new Recording();
        $recording->setRoom($room);
        $recording->setUser($user);
        $recording->setUid(md5($recordingId));
        $recording->setRecordingId($recordingId);
        $recording->setCreatedAt(new \DateTimeImmutable());
        $em->persist($recording);
        $em->flush();
        return $recording;
    }
}
