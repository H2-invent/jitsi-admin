<?php

namespace App\Tests\SipCaller;

use App\Entity\Repeat;
use App\Entity\Rooms;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\caller\CallerPrepareService;
use App\Service\RepeaterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * The dial-in number of a series conference belongs to the prototype room. Dialing it while an appointment
 * of the series is running has to end in the conference of that appointment instead of "not started".
 */
class SeriesCallerDialInTest extends WebTestCase
{
    public function testDialInIntoTheRunningAppointment(): void
    {
        $client = static::createClient([], ['HTTP_authorization' => 'Bearer TestApi']);
        $series = $this->createSeries($client, '-10 minutes', '+50 minutes');

        $this->jitsiRoomCreated($client, $series['appointmentUid']);
        $this->jitsiOccupantJoined($client, $series['appointmentUid']);

        $client->request('GET', '/api/v1/lobby/sip/room/' . $series['roomId']);
        self::assertResponseIsSuccessful();
        $first = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('ACCEPTED', $first['status'], 'the series number has to be dialable while an appointment is running');

        $client->request('GET', $first['links']['open'], ['caller_id' => '01511234567']);
        self::assertResponseIsSuccessful();
        $second = json_decode($client->getResponse()->getContent(), true);

        self::assertSame('STARTED', $second['state']);
        self::assertArrayHasKey('jwt', $second);
        self::assertStringStartsWith($series['appointmentUid'], $second['room_name'], 'the caller has to end up in the conference of the running appointment');
    }

    public function testDialInIntoASeriesWithoutRunningAppointmentDoesNotStart(): void
    {
        $client = static::createClient([], ['HTTP_authorization' => 'Bearer TestApi']);
        $series = $this->createSeries($client, '+1 day', '+1 day 60 minutes');

        $client->request('GET', '/api/v1/lobby/sip/room/' . $series['roomId']);
        self::assertResponseIsSuccessful();
        $first = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('ACCEPTED', $first['status']);

        $client->request('GET', $first['links']['open'], ['caller_id' => '01511234567']);
        self::assertResponseIsSuccessful();
        $second = json_decode($client->getResponse()->getContent(), true);

        self::assertSame('NOT_STARTED', $second['reason'], 'without a running appointment the conference is not started');
        self::assertArrayNotHasKey('jwt', $second);
    }

    /**
     * Converts the fixture room into a series with one appointment at the given time and gives the prototype
     * the dial-in number that a real installation hands out in the nightly caller prepare job.
     *
     * @return array{roomId: string, appointmentUid: string}
     */
    private function createSeries(KernelBrowser $client, string $appointmentStart, string $appointmentEnd): array
    {
        $client->loginUser(self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']));
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        // the series keeps the time of its prototype, the prototype itself is never used for a dial-in
        $room->setStart((new \DateTime())->modify('-10 minutes'));
        $room->setEnddate((new \DateTime())->modify('+50 minutes'));
        $manager->persist($room);
        $manager->flush();

        $repeater = new Repeat();
        $repeater->setRepeatType(0);
        $repeater->setRepeaterDays(7);
        $repeater->setRepetation(2);
        $repeater->setUid(md5(uniqid()));
        $repeater->setPrototyp($room);
        $repeater->setStartDate($room->getStart());
        $manager->persist($repeater);
        $manager->flush();

        $repeaterService = self::getContainer()->get(RepeaterService::class);
        $repeaterService->cleanRepeater($repeater);
        $repeaterService->createNewRepeater($repeater);
        $repeaterService->addUserRepeat($repeater);
        self::getContainer()->get(CallerPrepareService::class)->addCallerIdToRoom($room);
        self::assertNotNull($room->getCallerRoom(), 'the series needs its own dial-in number');

        $appointment = null;
        foreach ($roomRepo->findBy(['name' => 'TestMeeting: 0'], ['start' => 'ASC']) as $candidate) {
            if ($candidate->getRepeater() !== null) {
                $appointment = $candidate;
                break;
            }
        }
        self::assertNotNull($appointment, 'the series has to create appointments');
        $appointment->setStart((new \DateTime())->modify($appointmentStart));
        $appointment->setEnddate((new \DateTime())->modify($appointmentEnd));
        $appointment->setLobby(false);
        $manager->persist($appointment);
        $manager->flush();

        return ['roomId' => $room->getCallerRoom()->getCallerId(), 'appointmentUid' => $appointment->getUid()];
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
}
