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
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
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

class ScheduleControllerTest extends KernelTestCase
{

    private MockObject&ManagerRegistry $managerRegistry;

    private MockObject&LoggerInterface $logger;

    private MockObject&ParameterBagInterface $parameterBag;

    private ScheduleController $subject;

    public function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $translator = $this->bootKernel()->getContainer()->get('translator');
        $schedulingService = $this->createMock(SchedulingService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);

        $this->subject = new ScheduleController(
            $this->managerRegistry,
            $translator,
            $this->logger,
            $this->parameterBag,
            schedulingService: $schedulingService,
        );
    }

    public function testGenerateVoteCsv(): void
    {
        $room = $this->getRoomMock();
        $scheduling = $this->getSchedulingMock();
        $schedulingTime1 = $this->getSchedulingTimeMock();
        $schedulingTime2 = $this->getSchedulingTimeMock();
        $schedulingTimeUser1 = $this->getSchedulingTimeUserMock();
        $schedulingTimeUser2 = $this->getSchedulingTimeUserMock();
        $schedulingTimeUser3 = $this->getSchedulingTimeUserMock();
        $user1 = $this->getUserMock();
        $user2 = $this->getUserMock();
        $user3 = $this->getUserMock();
        $firstname1 = 'user1';
        $firstname2 = 'user2';
        $firstname3 = 'user3';
        $lastname1 = 'test1';
        $lastname2 = 'test2';
        $lastname3 = 'test3';
        $email1 = 'email1';
        $email2 = 'email2';
        $email3 = 'email3';

        $schedulingCollection = new ArrayCollection();
        $schedulingCollection->add($scheduling);

        $schedulingTimeCollection = new ArrayCollection();
        $schedulingTimeCollection->add($schedulingTime1);
        $schedulingTimeCollection->add($schedulingTime2);

        $schedulingTimeUserCollection1 = new ArrayCollection();
        $schedulingTimeUserCollection1->add($schedulingTimeUser1);
        $schedulingTimeUserCollection1->add($schedulingTimeUser2);

        $schedulingTimeUserCollection2 = new ArrayCollection();
        $schedulingTimeUserCollection2->add($schedulingTimeUser2);
        $schedulingTimeUserCollection2->add($schedulingTimeUser3);

        $dateTime1 = new DateTime('2023-10-31');
        $dateTime2 = (clone $dateTime1)->modify('+1 day');

        $room
            ->expects(self::once())
            ->method('getSchedulings')
            ->willReturn($schedulingCollection);

        $room
            ->expects(self::once())
            ->method('getName')
            ->willReturn('room');

        $scheduling
            ->expects(self::once())
            ->method('getSchedulingTimes')
            ->willReturn($schedulingTimeCollection);

        $schedulingTime1
            ->expects(self::once())
            ->method('getTime')
            ->willReturn($dateTime1);

        $schedulingTime2
            ->expects(self::once())
            ->method('getTime')
            ->willReturn($dateTime2);

        $schedulingTime1
            ->expects(self::once())
            ->method('getSchedulingTimeUsers')
            ->willReturn($schedulingTimeUserCollection1);

        $schedulingTime2
            ->expects(self::once())
            ->method('getSchedulingTimeUsers')
            ->willReturn($schedulingTimeUserCollection2);

        $schedulingTimeUser1
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($user1);

        $schedulingTimeUser2
            ->expects(self::exactly(2))
            ->method('getUser')
            ->willReturn($user2);

        $schedulingTimeUser3
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($user3);

        $user1
            ->expects(self::once())
            ->method('getFirstName')
            ->willReturn($firstname1);

        $user1
            ->expects(self::once())
            ->method('getLastName')
            ->willReturn($lastname1);

        $user1
            ->expects(self::exactly(3))
            ->method('getId')
            ->willReturn(1);

        $user1
            ->expects(self::once())
            ->method('getEmail')
            ->willReturn($email1);

        $schedulingTimeUser1
            ->expects(self::once())
            ->method('getAccept')
            ->willReturn(0);

        $user2
            ->expects(self::exactly(2))
            ->method('getFirstName')
            ->willReturn($firstname2);

        $user2
            ->expects(self::exactly(2))
            ->method('getLastName')
            ->willReturn($lastname2);

        $user2
            ->expects(self::exactly(5))
            ->method('getId')
            ->willReturn(2);

        $user2
            ->expects(self::once())
            ->method('getEmail')
            ->willReturn($email2);

        $schedulingTimeUser2
            ->expects(self::exactly(2))
            ->method('getAccept')
            ->willReturnOnConsecutiveCalls(1, 2);

        $user3
            ->expects(self::once())
            ->method('getFirstName')
            ->willReturn($firstname3);

        $user3
            ->expects(self::once())
            ->method('getLastName')
            ->willReturn($lastname3);

        $user3
            ->expects(self::exactly(3))
            ->method('getId')
            ->willReturn(0);

        $user3
            ->expects(self::once())
            ->method('getEmail')
            ->willReturn($email3);

        $schedulingTimeUser3
            ->expects(self::once())
            ->method('getAccept')
            ->willReturn(2);

        $actualResponse = $this->subject->generateVoteCsv($room);

        $expected = 'Name;Email;'.    $dateTime2->format('d-m-Y H:i:s') .';' .$dateTime1->format('d-m-Y H:i:s');
        $expected .= PHP_EOL . 'user1 test1;email1;null;Ja' . PHP_EOL . 'user2 test2;email2;Unter Vorbehalt;Nein' . PHP_EOL;
        $expected .= 'user3 test3;email3;Unter Vorbehalt;null';

        $this->assertEquals($expected, $actualResponse->getContent());
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

    private function getSchedulingTimeUserMock(): MockObject&SchedulingTimeUser
    {
        return $this->createMock(SchedulingTimeUser::class);
    }

    private function getUserMock(): MockObject&User
    {
        return $this->createMock(User::class);
    }

    private function getContainerMockWithSession(): ContainerInterface
    {
        $container = $this->getContainer();
        $requestStack = $this->createMock(RequestStack::class);
        $session = $this->createMock(FlashbagAwareSessionInterface::class);
        $flashbag = $this->createMock(FlashBagInterface::class);

        $container->set('request_stack', $requestStack);

        $requestStack
            ->method('getSession')
            ->willReturn($session);

        $session
            ->method('getFlashBag')
            ->willReturn($flashbag);

        return $container;
    }
}