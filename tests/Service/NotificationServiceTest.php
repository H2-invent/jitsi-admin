<?php

namespace App\Tests\Service;

use App\Repository\RoomsRepository;
use App\Repository\ServerRepository;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class NotificationServiceTest extends KernelTestCase
{
    private function fixtures(): array
    {
        $container = self::getContainer();

        return [
            $container->get(NotificationService::class),
            $container->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']),
            $container->get(UserRepository::class)->findOneBy(['email' => 'test@local4.de']),
            $container->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 1']),
            $container->get(ServerRepository::class)->findOneBy(['url' => 'meet.jit.si']),
        ];
    }

    public function testCreateIcsUsesPublishForModerator(): void
    {
        self::bootKernel();
        [$service, $user, , $room] = $this->fixtures();
        $room->setHostUrl('https://meet.jit.si');

        $ics = $service->createIcs($room, $user, 'https://meet.jit.si/join/abc');

        self::assertStringContainsString('BEGIN:VCALENDAR', $ics);
        self::assertStringContainsString('METHOD:PUBLISH', $ics);
        self::assertStringContainsString('SUMMARY:TestMeeting: 1', $ics);
        self::assertStringContainsString('DTSTART:', $ics);
        self::assertStringContainsString('DESCRIPTION:', $ics);
        self::assertStringContainsString('https://meet.jit.si/join/abc', $ics);
    }

    public function testCreateIcsUsesRequestedMethodForOtherAttendee(): void
    {
        self::bootKernel();
        [$service, , $other, $room] = $this->fixtures();
        $room->setHostUrl('https://meet.jit.si');

        $ics = $service->createIcs($room, $other, 'https://meet.jit.si/join/abc', 'REQUEST');

        self::assertStringContainsString('METHOD:REQUEST', $ics);
        self::assertStringContainsString('ORGANIZER;CN=Test User:MAILTO:' . $room->getModerator()->getEmail(), $ics);
    }

    public function testCreateIcsCancelKeepsCancelMethodForModerator(): void
    {
        self::bootKernel();
        [$service, $user, , $room] = $this->fixtures();
        $room->setHostUrl('https://meet.jit.si');

        $ics = $service->createIcs($room, $user, 'https://meet.jit.si/join/abc', 'CANCEL');

        self::assertStringContainsString('METHOD:CANCEL', $ics);
    }

    public function testSendNotificationSendsMailWithModeratorReplyTo(): void
    {
        self::bootKernel();
        [$service, $user, , $room, $server] = $this->fixtures();

        $result = $service->sendNotification('Notification body', 'Notification subject', $user, $server, $room);

        self::assertTrue($result);
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        self::assertEmailAddressContains($email, 'to', $user->getEmail());
        self::assertEmailAddressContains($email, 'reply-to', $room->getModerator()->getEmail());
    }

    public function testSendNotificationWorksWithoutRoom(): void
    {
        self::bootKernel();
        [$service, $user, , , $server] = $this->fixtures();

        $result = $service->sendNotification('Notification body', 'Notification subject', $user, $server);

        self::assertTrue($result);
        $this->assertEmailCount(1);
    }

    public function testSendCronSendsMailWithRoom(): void
    {
        self::bootKernel();
        [$service, $user, , $room, $server] = $this->fixtures();

        $result = $service->sendCron('Cron body', 'Cron subject', $user, $server, $room);

        self::assertTrue($result);
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        self::assertEmailAddressContains($email, 'to', $user->getEmail());
    }
}
