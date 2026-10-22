<?php

namespace App\Tests\SipCaller;

use App\Entity\Repeat;
use App\Entity\Rooms;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\RepeaterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * The dial-in works with the number that belongs to the appointment itself. Every appointment of a series
 * has its own number, the prototype of the series has none.
 */
class SeriesPerAppointmentDialInTest extends WebTestCase
{
    public function testDialInWithTheNumberOfTheAppointment(): void
    {
        $client = static::createClient([], ['HTTP_authorization' => 'Bearer TestApi']);
        $appointment = $this->createSeries($client)[0];

        // the appointment is running right now and a participant joined its conference
        $appointment->setStart((new \DateTime())->modify('-10 minutes'));
        $appointment->setEnddate((new \DateTime())->modify('+50 minutes'));
        $appointment->setLobby(false);
        $manager = $this->manager();
        $manager->persist($appointment);
        $manager->flush();
        $uid = $appointment->getUid();
        $this->jitsiRoomCreated($client, $uid);
        $this->jitsiOccupantJoined($client, $uid);

        $roomId = $appointment->getCallerRoom()->getCallerId();
        $client->request('GET', '/api/v1/lobby/sip/room/' . $roomId);
        self::assertResponseIsSuccessful();
        $first = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('ACCEPTED', $first['status']);

        $client->request('GET', $first['links']['open'], ['caller_id' => '01511234567']);
        self::assertResponseIsSuccessful();
        $second = json_decode($client->getResponse()->getContent(), true);

        self::assertSame('STARTED', $second['state']);
        self::assertArrayHasKey('jwt', $second);
        self::assertStringStartsWith($uid, $second['room_name'], 'the caller has to end up in the conference of the appointment he dialed');
    }

    public function testDialInWithAnUnknownNumberIsRejected(): void
    {
        $client = static::createClient([], ['HTTP_authorization' => 'Bearer 123456']);
        $this->createSeries($client);

        $client->request('GET', '/api/v1/lobby/sip/room/000000');
        self::assertResponseIsSuccessful();
        self::assertSame(
            ['status' => 'ROOM_ID_UKNOWN', 'reason' => 'ROOM_ID_UKNOWN', 'links' => []],
            json_decode($client->getResponse()->getContent(), true)
        );
    }

    /**
     * @return Rooms[]
     */
    private function createSeries(KernelBrowser $client): array
    {
        $client->loginUser(self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']));
        $manager = $this->manager();
        $roomRepo = $this->rooms();
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $room->setStart((new \DateTime())->modify('-10 minutes'));
        $room->setEnddate((new \DateTime())->modify('+50 minutes'));
        $manager->persist($room);
        $manager->flush();

        $repeat = new Repeat();
        $repeat->setRepeatType(0);
        $repeat->setRepeaterDays(7);
        $repeat->setRepetation(2);
        $repeat->setUid(md5(uniqid()));
        $repeat->setPrototyp($room);
        $repeat->setStartDate($room->getStart());
        $manager->persist($repeat);
        $manager->flush();

        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $repeaterService->cleanRepeater($repeat);
        $repeaterService->createNewRepeater($repeat);
        $repeaterService->addUserRepeat($repeat);

        $manager->clear();
        $appointments = [];
        foreach ($roomRepo->findBy(['name' => 'TestMeeting: 0'], ['start' => 'ASC']) as $candidate) {
            if ($candidate->getRepeater() !== null) {
                $appointments[] = $candidate;
            }
        }
        self::assertCount(2, $appointments);

        return $appointments;
    }

    private function jitsiRoomCreated(KernelBrowser $client, string $uid): void
    {
        $client->request(
            'POST',
            '/jitsi/events/room/created',
            [],
            [],
            $this->webhookHeader(),
            json_encode(
                [
                'event_name' => 'muc-room-created',
                'created_at' => time(),
                'room_name' => $uid,
                'is_breakout' => false,
                'room_jid' => $uid . '@conference.testserver.de',
                ]
            )
        );
        self::assertResponseIsSuccessful();
    }

    private function jitsiOccupantJoined(KernelBrowser $client, string $uid): void
    {
        $client->request(
            'POST',
            '/jitsi/events/occupant/joined',
            [],
            [],
            $this->webhookHeader(),
            json_encode(
                [
                'event_name' => 'muc-occupant-joined',
                'room_name' => $uid,
                'is_breakout' => false,
                'room_jid' => $uid . '@conference.testserver.de',
                'occupant' => ['occupant_jid' => 'abcd-1234@jitsi01/xyz', 'joined_at' => time(), 'name' => 'Browser Participant'],
                ]
            )
        );
        self::assertResponseIsSuccessful();
    }

    private function webhookHeader(): array
    {
        return [
            'HTTP_authorization' => 'Bearer ' . self::getContainer()->get('parameter_bag')->get('JITSI_EVENTS_TOKEN'),
            'CONTENT_TYPE' => 'application/json',
        ];
    }

    private function rooms(): RoomsRepository
    {
        return self::getContainer()->get(RoomsRepository::class);
    }

    private function manager(): EntityManagerInterface
    {
        return self::getContainer()->get(EntityManagerInterface::class);
    }
}
