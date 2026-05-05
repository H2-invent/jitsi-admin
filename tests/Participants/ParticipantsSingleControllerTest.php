<?php

namespace App\Tests\Participants;

use App\Repository\RoomsRepository;
use App\Repository\RoomsUserRepository;
use App\Repository\UserRepository;
use App\Service\Deputy\DeputyService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use function PHPUnit\Framework\assertEquals;

class ParticipantsSingleControllerTest extends WebTestCase
{
    public function testCorrectInvite(): void
    {
        $client = static::createClient();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(3, $room->getUser()->count());
        $organizer = $room->getModerator();
        $client->loginUser($organizer);

        $crawler = $client->request('GET', '/room/participant/add/' . $room->getId());
        self::assertResponseIsSuccessful();
        $crawler = $client->request('POST', '/room/participant/add_single/' . $room->getId(), content: json_encode(['participant' => ['test@local4.de']]));
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(4, $room->getUser()->count());
        self::assertResponseStatusCodeSame(200);
        assertEquals('{"invalidMember":[],"validMember":["test@local4.de"]}', $client->getResponse()->getContent());
    }
    public function testEmptyInvite(): void
    {
        $client = static::createClient();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(3, $room->getUser()->count());
        $organizer = $room->getModerator();
        $client->loginUser($organizer);

        $crawler = $client->request('GET', '/room/participant/add/' . $room->getId());
        self::assertResponseIsSuccessful();
        $crawler = $client->request('POST', '/room/participant/add_single/' . $room->getId(), content: json_encode(['wrongEntity' => ['test@local4.de']]));
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(3, $room->getUser()->count());
        self::assertResponseStatusCodeSame(200);
        assertEquals('{"error":true}', $client->getResponse()->getContent());
    }
    public function testInvalidParticipantInvite(): void
    {
        $client = static::createClient();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(3, $room->getUser()->count());
        $userRepo = self::getContainer()->get(UserRepository::class);
        $organizer = $userRepo->findOneBy(['email' => 'test@local4.de']);
        $client->loginUser($organizer);


        $crawler = $client->request('POST', '/room/participant/add_single/' . $room->getId(), content: json_encode(['participant' => ['test@local.de']]));
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(3, $room->getUser()->count());
        self::assertResponseStatusCodeSame(200);
        assertEquals('{"error":true}', $client->getResponse()->getContent());
    }
    public function testParticipantIsCreatorInvite(): void
    {
        $client = static::createClient();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(3, $room->getUser()->count());
        $userRepo = self::getContainer()->get(UserRepository::class);
        $organizer = $userRepo->findOneBy(['email' => 'test@local4.de']);
        $room->setCreator($organizer);
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->persist($room);
        $deputyService = self::getContainer()->get(DeputyService::class);
        $moderator = $room->getModerator();
        $deputyService->setDeputy($moderator,$organizer);
        $em->persist($moderator);
        $em->flush();
        $client->loginUser($organizer);


        $crawler = $client->request('POST', '/room/participant/add_single/' . $room->getId(), content: json_encode(['participant' => ['test@local4.de']]));
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        self::assertEquals(3, $room->getUser()->count());
        self::assertResponseStatusCodeSame(200);
        assertEquals('{"invalidMember":["test@local4.de"],"validMember":[]}', $client->getResponse()->getContent());
    }

}
