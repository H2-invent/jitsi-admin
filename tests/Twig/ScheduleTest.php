<?php

namespace App\Tests\Twig;

use App\Entity\SchedulingTimeUser;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use App\Twig\Schedule;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\TwigFunction;

class ScheduleTest extends KernelTestCase
{
    private function extension(): Schedule
    {
        return self::getContainer()->get(Schedule::class);
    }

    private function functionByName(Schedule $extension, string $name): TwigFunction
    {
        foreach ($extension->getFunctions() as $function) {
            if ($function->getName() === $name) {
                return $function;
            }
        }
        self::fail(sprintf('Twig function "%s" not registered', $name));
    }

    public function testGetFunctionsReturnsExpectedTwigFunctions(): void
    {
        self::bootKernel();
        $extension = $this->extension();
        $functions = $extension->getFunctions();
        $names = array_map(static fn(TwigFunction $function) => $function->getName(), $functions);

        $this->assertSame(
            ['scheduleNumber', 'scheduleUser', 'scheduleOwnJoice', 'scheduleUserHasVoted', 'myScheduledMeeting'],
            $names
        );
        $this->assertSame([$extension, 'scheduleNumber'], $functions[0]->getCallable());
        $this->assertSame([$extension, 'scheduleUser'], $functions[1]->getCallable());
        $this->assertSame([$extension, 'scheduleOwnJoice'], $functions[2]->getCallable());
        $this->assertSame([$extension, 'scheduleUserHasVoted'], $functions[3]->getCallable());
        $this->assertSame([$extension, 'myScheduledMeeting'], $functions[4]->getCallable());
    }

    public function testScheduleNumberAndScheduleUserCountVotesByAcceptType(): void
    {
        self::bootKernel();
        $extension = $this->extension();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $user = self::getContainer()->get(UserRepository::class)->findOneByUsername('test@local.de');
        $user2 = self::getContainer()->get(UserRepository::class)->findOneByUsername('test2@local.de');
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'Termin finden: 0']);
        $times = $room->getSchedulings()->first()->getSchedulingTimes()->toArray();
        $time0 = $times[0];
        $time1 = $times[1];

        $accept = (new SchedulingTimeUser())->setUser($user)->setScheduleTime($time0)->setAccept(1);
        $decline = (new SchedulingTimeUser())->setUser($user2)->setScheduleTime($time0)->setAccept(0);
        $other = (new SchedulingTimeUser())->setUser($user2)->setScheduleTime($time1)->setAccept(1);
        foreach ([$accept, $decline, $other] as $vote) {
            $em->persist($vote);
            $vote->getScheduleTime()->addSchedulingTimeUser($vote);
        }
        $em->flush();

        $this->assertSame(1, $extension->scheduleNumber($time0, 1));
        $this->assertSame(1, $extension->scheduleNumber($time0, 0));
        $this->assertSame(1, $extension->scheduleNumber($time1, 1));
        $this->assertSame(0, $extension->scheduleNumber($time1, 0));

        $this->assertCount(1, $extension->scheduleUser($time0, 1));
        $this->assertSame([$accept], $extension->scheduleUser($time0, 1));
        $this->assertSame(
            [$accept],
            call_user_func($this->functionByName($extension, 'scheduleUser')->getCallable(), $time0, 1)
        );
    }

    public function testScheduleOwnJoiceReturnsAcceptOrNullWhenUserDidNotVote(): void
    {
        self::bootKernel();
        $extension = $this->extension();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $user = self::getContainer()->get(UserRepository::class)->findOneByUsername('test@local.de');
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'Termin finden: 0']);
        $times = $room->getSchedulings()->first()->getSchedulingTimes()->toArray();
        $time0 = $times[0];
        $time1 = $times[1];

        $vote = (new SchedulingTimeUser())->setUser($user)->setScheduleTime($time0)->setAccept(2);
        $em->persist($vote);
        $em->flush();

        $this->assertSame(2, $extension->scheduleOwnJoice($user, $time0));
        $this->assertNull($extension->scheduleOwnJoice($user, $time1));
        $this->assertSame(
            2,
            call_user_func($this->functionByName($extension, 'scheduleOwnJoice')->getCallable(), $user, $time0)
        );
    }

    public function testScheduleUserHasVotedReflectsExistingVotesForRoom(): void
    {
        self::bootKernel();
        $extension = $this->extension();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $user = self::getContainer()->get(UserRepository::class)->findOneByUsername('test@local.de');
        $userWithoutVote = self::getContainer()->get(UserRepository::class)->findOneByUsername('test@local3.de');
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'Termin finden: 0']);
        $time0 = $room->getSchedulings()->first()->getSchedulingTimes()->first();

        $this->assertFalse($extension->scheduleUserHasVoted($user, $room));

        $vote = (new SchedulingTimeUser())->setUser($user)->setScheduleTime($time0)->setAccept(1);
        $em->persist($vote);
        $em->flush();

        $this->assertTrue($extension->scheduleUserHasVoted($user, $room));
        $this->assertFalse($extension->scheduleUserHasVoted($userWithoutVote, $room));
        $this->assertTrue(
            call_user_func($this->functionByName($extension, 'scheduleUserHasVoted')->getCallable(), $user, $room)
        );
    }

    public function testMyScheduledMeetingReturnsScheduledRoomsWhereUserParticipates(): void
    {
        self::bootKernel();
        $extension = $this->extension();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $user = self::getContainer()->get(UserRepository::class)->findOneByUsername('test@local.de');
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'Termin finden: 0']);

        $room->addUser($user);
        $em->persist($room);
        $em->flush();

        $names = array_map(
            static fn($scheduledRoom) => $scheduledRoom->getName(),
            $extension->myScheduledMeeting($user)
        );

        $this->assertContains('Termin finden: 0', $names);
        $this->assertContains(
            'Termin finden: 0',
            array_map(
                static fn($scheduledRoom) => $scheduledRoom->getName(),
                call_user_func($this->functionByName($extension, 'myScheduledMeeting')->getCallable(), $user)
            )
        );
    }
}
