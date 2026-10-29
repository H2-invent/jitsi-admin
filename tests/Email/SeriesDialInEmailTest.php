<?php

namespace App\Tests\Email;

use App\Entity\Repeat;
use App\Entity\Rooms;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Service\caller\CallerPrepareService;
use App\Service\RepeaterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;

/**
 * Planning mails for a series carry no dial-in number. The reminder mail carries the dial-in number and
 * the personal PIN of the conference it reminds about.
 */
class SeriesDialInEmailTest extends KernelTestCase
{
    public function testPlanningMailContainsNoDialInNumber(): void
    {
        [$prototype, $appointments] = $this->createSeries();
        $prototype->getServer()->setJigasiNumberUrl(json_encode(['numbers' => ['DE' => ['0123456789']]]));
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local2.de']);

        $html = $this->render('email/repeaterAddUser.html.twig', ['room' => $prototype, 'user' => $user]);

        self::assertStringNotContainsString('Sie können sich auch einfach per Telefon in die Konferenz einwählen.', $html);
        foreach ($appointments as $appointment) {
            self::assertNotNull($appointment->getCallerRoom());
            self::assertStringNotContainsString($appointment->getCallerRoom()->getCallerId(), $html, 'the planning mail must not hand out any dial-in number');
        }
    }

    public function testReminderMailContainsTheDialInNumberAndPersonalPin(): void
    {
        [$prototype, $appointments] = $this->createSeries();
        $appointment = $appointments[0];
        $appointment->getServer()->setJigasiNumberUrl(json_encode(['numbers' => ['DE' => ['0123456789']]]));
        $user = self::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'test@local2.de']);

        $pin = null;
        foreach ($appointment->getCallerIds() as $callerId) {
            if ($callerId->getUser() === $user) {
                $pin = $callerId->getCallerId();
            }
        }
        self::assertNotNull($pin);

        $html = $this->render('email/rememberUser.html.twig', ['room' => $appointment, 'user' => $user]);

        self::assertStringContainsString($appointment->getCallerRoom()->getCallerId(), $html, 'the reminder mail has to contain the room number of the conference');
        self::assertStringContainsString($pin, preg_replace('/\s+/', '', $html), 'the reminder mail has to contain the personal PIN of the conference');
    }

    private function render(string $template, array $context): string
    {
        return self::getContainer()->get(Environment::class)->render($template, $context);
    }

    /**
     * @return array{0: Rooms, 1: Rooms[]}
     */
    private function createSeries(): array
    {
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'TestMeeting: 0']);
        $room->setStart((new \DateTime())->modify('+1 day')->setTime(10, 0));
        $room->setEnddate((clone $room->getStart())->modify('+60 minutes'));
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
        $prototype = $roomRepo->find($room->getId());
        $appointments = [];
        foreach ($roomRepo->findBy(['name' => 'TestMeeting: 0'], ['start' => 'ASC']) as $candidate) {
            if ($candidate->getRepeater() !== null) {
                $appointments[] = $candidate;
            }
        }

        return [$prototype, $appointments];
    }
}
