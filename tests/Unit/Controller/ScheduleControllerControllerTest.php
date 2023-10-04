<?php

namespace App\Tests\Unit\Controller;

use App\Controller\ScheduleController;
use App\Entity\Rooms;
use App\Entity\Scheduling;
use App\Entity\SchedulingTime;
use App\Entity\SchedulingTimeUser;
use App\Entity\User;
use App\Service\SchedulingService;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Router;
use Symfony\Contracts\Translation\TranslatorInterface;
use function PHPUnit\Framework\exactly;

class ScheduleControllerControllerTest extends WebTestCase
{

    private MockObject&ManagerRegistry $managerRegistry;

    private MockObject&LoggerInterface $logger;

    private MockObject&ParameterBagInterface $parameterBag;

    private ScheduleController $subject;
    private KernelBrowser $client;
    public function setUp(): void
    {

        $this->managerRegistry = $this->createMock(ManagerRegistry::class);

        $schedulingService = $this->createMock(SchedulingService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);

    }

    public function testGenerateCsvSkipsOnNoScheduling(): void
    {

        $this->client = static::createClient();
        $room = $this->getRoomMock();

        $room
            ->method('getSchedulings')
            ->willReturn(new ArrayCollection());
        $room
            ->expects(self::once())
            ->method('getId')
            ->willReturn(1);

        $crawler = $this->client->request('GET', '/schedule/download/csv/'.$room->getId());


        $this->assertResponseRedirects('/room/dashboard');
    }

    public function testGenerateCsvSkipsOnNoVotes(): void
    {
        $this->client = static::createClient();

        $room = $this->getRoomMock();
        $scheduling = $this->getSchedulingMock();
        $schedulingTime = $this->getSchedulingTimeMock();
        $dateTime = new DateTime();

        $schedulingCollection = new ArrayCollection();
        $schedulingCollection->add($scheduling);

        $schedulingTimeCollection = new ArrayCollection();
        $schedulingTimeCollection->add($schedulingTime);

        $room

            ->method('getSchedulings')
            ->willReturn($schedulingCollection);
        $room
            ->expects(self::once())
            ->method('getId')
            ->willReturn(1);
        $scheduling

            ->method('getSchedulingTimes')
            ->willReturn($schedulingTimeCollection);

        $schedulingTime

            ->method('getTime')
            ->willReturn($dateTime);



        $crawler = $this->client->request('GET', '/schedule/download/csv/'.$room->getId());

        $this->assertResponseRedirects('/room/dashboard');

    }

    private function getRoomMock(): MockObject&Rooms
    {
        return $this->createMock(Rooms::class);
    }

    private function getSchedulingMock(): MockObject&Scheduling
    {
        return $this->createMock(Scheduling::class);
    }

    private function getSchedulingTimeMock(): MockObject&SchedulingTime
    {
        return $this->createMock(SchedulingTime::class);
    }


}