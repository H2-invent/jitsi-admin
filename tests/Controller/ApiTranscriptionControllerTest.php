<?php

namespace App\Tests\Controller;

use App\Repository\RoomsRepository;
use App\Repository\TranscriptionRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiTranscriptionControllerTest extends WebTestCase
{
    public function testCreateWithInvalidToken(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/v1/transcription', [
            'roomUid' => '561ghj984ssdfdf',
            'transcription' => 'Hello world',
        ], [], ['HTTP_AUTHORIZATION' => 'Bearer invalid']);

        $this->assertResponseStatusCodeSame(403);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['error']);
        $this->assertSame('No Bearer Token', $data['text']);
    }

    public function testCreateWithUnknownRoom(): void
    {
        $client = static::createClient();
        $token = self::getContainer()->getParameter('API_TOKEN_BEARER_TRANSCRIPTION');
        $client->request('POST', '/api/v1/transcription', [
            'roomUid' => 'does-not-exist',
            'transcription' => 'Hello world',
        ], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['error']);
        $this->assertSame('Could not find room', $data['text']);
    }

    public function testCreateWithMissingTranscription(): void
    {
        $client = static::createClient();
        $token = self::getContainer()->getParameter('API_TOKEN_BEARER_TRANSCRIPTION');
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['uidReal' => '561ghj984ssdfdf']);
        $client->request('POST', '/api/v1/transcription', [
            'roomUid' => $room->getUidReal(),
        ], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['error']);
        $this->assertSame('No transcription transmitted', $data['text']);
    }

    public function testCreateStoresTranscription(): void
    {
        $client = static::createClient();
        $token = self::getContainer()->getParameter('API_TOKEN_BEARER_TRANSCRIPTION');
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['uidReal' => '561ghj984ssdfdf']);

        $client->request('POST', '/api/v1/transcription', [
            'roomUid' => $room->getUidReal(),
            'transcription' => 'Hello world',
        ], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['error']);
        $this->assertStringContainsString('Hello world', self::getContainer()->get(TranscriptionRepository::class)->findOneBy(['room' => $room])->getText());
    }
}
