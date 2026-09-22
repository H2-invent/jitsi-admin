<?php

namespace App\Tests\Join;

use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\RoomService;
use App\UtilsHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class JoinControllerTest extends WebTestCase
{
    public function testjoinRoomBrowser(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $client->loginUser($testUser);
        $crawler = $client->request('GET', '/room/join/b/' . $room->getId());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        self::assertStringContainsString('eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJqaXRzaV9hZG1pbiIsImlzcyI6ImppdHNpSWQiLCJzdWIiOiJtZWV0LmppdC5zaTIiLCJyb29tIjoiMTIzNDU2NzgxIiwiY29udGV4dCI6eyJyb29tIjp7Im5hbWUiOiJUZXN0TWVldGluZzogMSIsImlzRTJFRUVuYWJsZWQiOmZhbHNlfSwidXNlciI6eyJuYW1lIjoiVXNlciwgVGVzdCwgdGVzdEBsb2NhbC5kZSIsImxhbmd1YWdlIjoiZGUiLCJ0aW1lem9uZSI6IkV1cm9wZS9CZXJsaW4ifX0sIm1vZGVyYXRvciI6dHJ1ZSwibG9iYnlNb2RlcmF0b3IiOnRydWUsInRoZW1lIjp7ImNvbG9yU2NoZW1lIjoibGlnaHQifX0.DJWpfs5KQiT-3Emb6yvrd16N6zZ3WXkwjibu3gmwP1g', (string) $client->getResponse()->getContent());
    }
    public function testjoinRoomApp(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        // retrieve the test user
        $testUser = $userRepository->findOneByUsername('test@local.de');
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 1']);
        $client->loginUser($testUser);
        $crawler = $client->request('GET', '/room/join/a/' . $room->getId());
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $jwtFactory = self::getContainer()->get(RoomService::class);
        $jwt = $jwtFactory->generateJwt($room, $testUser, 'User, Test, test@local.de');
        $slugyfy = UtilsHelper::slugify($room->getName());
        self::assertTrue($client->getResponse()->isRedirect('jitsi-meet://' . $room->getServer()->getUrl() . '/' . $room->getUid() . '?jwt=' . $jwt . '#config.subject=%22' . $slugyfy . '%22'));
    }
}
