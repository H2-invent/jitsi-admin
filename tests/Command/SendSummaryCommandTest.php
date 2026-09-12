<?php

namespace App\Tests\Command;

use App\Command\SendSummaryCommand;
use App\Entity\Rooms;
use App\Repository\RoomsRepository;
use App\Service\Summary\SendSummaryViaEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SendSummaryCommandTest extends KernelTestCase
{
    public function testSendSummaryForRoom(): void
    {
        self::bootKernel();

        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'This Room has no participants and fixed room']);
        $this->assertInstanceOf(Rooms::class, $room);
        $participants = sizeof($room->getUser());
        $this->assertSame(1, $participants);

        $sendSummaryService = $this->createMock(SendSummaryViaEmailService::class);
        $sendSummaryService->expects($this->once())
            ->method('sendSummaryForRoom')
            ->with($room);

        $command = new SendSummaryCommand(
            self::getContainer()->get(EntityManagerInterface::class),
            $sendSummaryService
        );
        $tester = new CommandTester($command);
        $tester->execute(['roomid' => $room->getId()]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('We send the summary for This Room has no participants and fixed room to 1 participants', $tester->getDisplay());
    }
}
