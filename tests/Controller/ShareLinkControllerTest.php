<?php

namespace App\Tests\Controller;

use App\Entity\Subscriber;
use App\Entity\Waitinglist;
use App\Entity\Rooms;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ShareLinkControllerTest extends WebTestCase
{
    private function makeRoomPublic(string $roomName = 'TestMeeting: 1'): Rooms
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => $roomName]);
        $room->setPublic(true);
        $room->setUidModerator('moderator-uid-' . uniqid());
        $room->setUidParticipant('participant-uid-' . uniqid());
        $em->persist($room);
        $em->flush();
        return $room;
    }

    public function testIndexRendersShareLinkModal(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $room = $this->makeRoomPublic();
        $client->loginUser($user);

        $client->request('GET', '/room/share/link/' . $room->getId());

        $this->assertResponseIsSuccessful();
    }

    public function testIndexRejectsNonPublicRoom(): void
    {
        $client = static::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 1']);
        $room->setPublic(false);
        $em->persist($room);
        $em->flush();
        $client->loginUser($user);

        $client->request('GET', '/room/share/link/' . $room->getId());

        $this->assertResponseStatusCodeSame(404);
    }

    public function testWaitinglistAcceptAddsUserToRoom(): void
    {
        $client = static::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $moderator = $userRepo->findOneBy(['email' => 'test@local.de']);
        $participant = $userRepo->findOneBy(['email' => 'test@local4.de']);
        $room = $this->makeRoomPublic();

        $waitinglist = new Waitinglist();
        $waitinglist->setUser($participant);
        $waitinglist->setRoom($room);
        $waitinglist->setCreatedAt(new \DateTime());
        $em->persist($waitinglist);
        $em->flush();
        $id = $waitinglist->getId();

        $client->loginUser($moderator);
        $client->request('GET', '/room/share/link/accetwaitinglist/' . $id);

        $this->assertResponseIsSuccessful();
        $this->assertSame(['error' => false], json_decode($client->getResponse()->getContent(), true));
        $this->assertNull(self::getContainer()->get(EntityManagerInterface::class)->getRepository(Waitinglist::class)->find($id));
    }

    public function testParticipantsRendersSubscriptionForm(): void
    {
        $client = static::createClient();
        $room = $this->makeRoomPublic();

        $client->request('GET', '/subscribe/self/' . $room->getUidModerator());

        $this->assertResponseIsSuccessful();
    }

    public function testDoupleOptinConfirmsSubscriber(): void
    {
        $client = static::createClient();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local4.de']);
        $room = $this->makeRoomPublic();

        $subscriber = new Subscriber();
        $subscriber->setUser($user);
        $subscriber->setRoom($room);
        $subscriber->setUid('subscriber-uid-' . uniqid());
        $em->persist($subscriber);
        $em->flush();

        $client->request('GET', '/subscribe/optIn/' . $subscriber->getUid());

        $this->assertResponseIsSuccessful();
        $this->assertNull(self::getContainer()->get(EntityManagerInterface::class)->getRepository(Subscriber::class)->findOneBy(['uid' => $subscriber->getUid()]));
    }
}
