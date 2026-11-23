<?php

namespace App\Tests\Controller;

use App\Entity\Transcription;
use App\Repository\RoomsRepository;
use App\Repository\TranscriptionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TranscriptionControllerTest extends WebTestCase
{
    private function createTranscription(string $roomName, string $text = 'Hello transcription'): array
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => $roomName]);
        $transcription = new Transcription();
        $transcription->setRoom($room);
        $transcription->setText($text);
        $em->persist($transcription);
        $em->flush();
        return [$transcription, $room];
    }

    public function testDownloadReturnsTranscription(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        [$transcription] = $this->createTranscription('TestMeeting: 1');
        $client->loginUser($user);

        $client->request('GET', '/room/transcription/' . $transcription->getId() . '/download');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Hello transcription', $client->getResponse()->getContent());
    }

    public function testDownloadRejectsForeignKey(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local4.de']);
        [$transcription] = $this->createTranscription('TestMeeting: 1');
        $client->loginUser($user);

        $client->request('GET', '/room/transcription/' . $transcription->getId() . '/download');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testRemoveDeletesTranscription(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        [$transcription] = $this->createTranscription('TestMeeting: 1');
        $id = $transcription->getId();
        $client->loginUser($user);

        $client->request('POST', '/room/transcription/' . $id . '/remove');

        $this->assertResponseIsSuccessful();
        $this->assertSame(['error' => false], json_decode($client->getResponse()->getContent(), true));
        $this->assertNull(self::getContainer()->get(TranscriptionRepository::class)->find($id));
    }

    public function testModalRendersTranscriptions(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        [, $room] = $this->createTranscription('TestMeeting: 1');
        $client->loginUser($user);

        $client->request('GET', '/room/transcriptions/modal/' . $room->getId());

        $this->assertResponseIsSuccessful();
    }

    public function testToggleEnablesTranscription(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        [, $room] = $this->createTranscription('TestMeeting: 1');
        $client->loginUser($user);

        $client->request(
            'POST',
            '/room/transcriptions/toggle/' . $room->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['enabled' => true])
        );

        $this->assertResponseIsSuccessful();
        $this->assertSame(['error' => false], json_decode($client->getResponse()->getContent(), true));
        self::getContainer()->get(EntityManagerInterface::class)->refresh($room);
        $this->assertTrue($room->isEnableTranscription());
    }

    public function testToggleRejectsInvalidJson(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        [, $room] = $this->createTranscription('TestMeeting: 1');
        $client->loginUser($user);

        $client->request(
            'POST',
            '/room/transcriptions/toggle/' . $room->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'not-json'
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['error']);
        $this->assertSame('Invalid JSON', $data['message']);
    }
}
