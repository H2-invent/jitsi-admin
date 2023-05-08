<?php

namespace App\Tests\PublicConference;

use App\Controller\PublicConferenceController;
use App\Repository\RoomsRepository;
use App\Repository\ServerRepository;
use App\Service\PublicConference\PublicConferenceService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PublicConferenceControllerTest extends WebTestCase
{
    public function testForm(): void
    {
        $client = static::createClient();
        $publicConference = self::getContainer()->get(PublicConferenceService::class);
        $serverRepo = self::getContainer()->get(ServerRepository::class);
        $server = $serverRepo->findOneBy(['url' => 'meet.jit.si']);
        $publicConferenceController = self::getContainer()->get(PublicConferenceController::class);
        $publicConferenceController->setServer($server);
        $crawler = $client->request('GET', '/m');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Ein Meetling starten');
        $buttonCrawlerNode = $crawler->selectButton('Los gehts!');
        $form = $buttonCrawlerNode->form();
        $publicConferenceController->setServer($server);
        $client->submit(
            $form,
            [
                'public_conference[roomName]' => 'testMyRoom'
            ]
        );
    }

    public function testConference(): void
    {
        $client = static::createClient();
        $publicConference = self::getContainer()->get(PublicConferenceService::class);
        $serverRepo = self::getContainer()->get(ServerRepository::class);
        $server = $serverRepo->findOneBy(['url' => 'meet.jit.si']);
        $publicConferenceController = self::getContainer()->get(PublicConferenceController::class);
        $publicConferenceController->setServer($server);
        $room = $publicConference->createNewRoomFromName('testMyRoom', $server);
        $crawler = $client->request('GET', '/m/' . $room->getName());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('title', 'testmyroom');
    }

    public function testCrazyConference(): void
    {
        $client = static::createClient();
        $publicConference = self::getContainer()->get(PublicConferenceService::class);
        $serverRepo = self::getContainer()->get(ServerRepository::class);
        $server = $serverRepo->findOneBy(['url' => 'meet.jit.si']);
        $publicConferenceController = self::getContainer()->get(PublicConferenceController::class);
        $publicConferenceController->setServer($server);


        $crawler = $client->request('GET', '/m/crazytestingthisroom');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('title', 'crazytestingthisroom');
    }
}
