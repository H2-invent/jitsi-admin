<?php

namespace App\Tests\Controller;

use App\Entity\CallerId;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DownloadParticipantsListControllerTest extends WebTestCase
{
    public function testIndexReturnsPdfForModerator(): void
    {
        $client = static::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 1']);

        foreach ($room->getUser() as $roomUser) {
            $callerId = new CallerId();
            $callerId->setRoom($room);
            $callerId->setUser($roomUser);
            $callerId->setCallerId('55' . $roomUser->getId());
            $callerId->setCreatedAt(new \DateTime());
            $em->persist($callerId);
        }
        $em->flush();

        $client->loginUser($user);

        ob_start();
        $client->request('GET', '/room/download/participants/list?room=' . $room->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSame('application/pdf', $client->getResponse()->headers->get('Content-type'));
        $this->assertStringStartsWith('%PDF', $client->getResponse()->getContent());
    }

    public function testIndexWithoutRoomReturnsNotFound(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $client->loginUser($user);

        $client->request('GET', '/room/download/participants/list?room=999999');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testIndexRejectsForeignKey(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local4.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 1']);
        $client->loginUser($user);

        $client->request('GET', '/room/download/participants/list?room=' . $room->getId());

        $this->assertResponseStatusCodeSame(404);
    }
}
