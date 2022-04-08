<?php


namespace App\Service;


use App\Entity\Repeat;
use App\Entity\Rooms;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Domain\Entity\Event;
use Eluceo\iCal\Domain\Entity\TimeZone;
use Eluceo\iCal\Domain\ValueObject\Date;
use Eluceo\iCal\Domain\ValueObject\DateTime;
use Eluceo\iCal\Domain\ValueObject\EmailAddress;
use Eluceo\iCal\Domain\ValueObject\Location;
use Eluceo\iCal\Domain\ValueObject\Organizer;
use Eluceo\iCal\Domain\ValueObject\SingleDay;
use Eluceo\iCal\Domain\ValueObject\TimeSpan;
use Eluceo\iCal\Presentation\Factory\CalendarFactory;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class IcalService
{
    private $licenseService;
    private $em;
    private $userService;
    private $user;
    private $translator;
    private $rooms;


    public function __construct(TranslatorInterface $translator, LicenseService $licenseService, EntityManagerInterface $entityManager, UserService $userService)
    {
        $this->licenseService = $licenseService;
        $this->em = $entityManager;
        $this->userService = $userService;
        $this->translator = $translator;
    }

    public function getIcal(User $user)
    {
        $isEnterprise = false;
        $this->user = $user;
        $server = $user->getServers();
        foreach ($server as $data) {
            if ($this->licenseService->verify($data)) {
                $isEnterprise = true;
            }
        }
        $value = '';

        if ($isEnterprise) {
            $this->initRooms($this->user);
            $value = $this->getIcalString($this->user);
        } else {
            $cache = new FilesystemAdapter();
            $value = $cache->get('ical_' . $user->getUid(), function (ItemInterface $item) {
                $item->expiresAfter(900);
                $this->initRooms($this->user);
                return $this->getIcalString($this->user);
            });
        }

        return $value;

    }

    public function initRooms(User $user)
    {
        $this->rooms = $this->em->getRepository(Rooms::class)->findRoomsFutureAndPast($user, '-1 month');
        $this->rooms = array_values($this->rooms);
    }

    public function getIcalString(User $user)
    {
        $cal = new Calendar();

        $timeZone = TimeZoneService::getTimeZone($user);
        if (!$timeZone) {
            $timeZone = new \DateTimeZone(date_default_timezone_get());
        }

        $start = new \DateTime();
        $end = new \DateTime();


        if (sizeof($this->rooms) > 1) {
            $start = (clone $this->rooms[0]->getStart())->modify('-1year');
            $end = (clone $this->rooms[sizeof($this->rooms) - 1]->getEndDate())->modify('+1year');
        }

        $cal->addTimeZone(TimeZone::createFromPhpDateTimeZone($timeZone,
            new DateTimeImmutable($start->format('Y-m-d H:i:s'), $timeZone),
            new DateTimeImmutable($end->format('Y-m-d H:i:s'), $timeZone)
        )
        );
        foreach ($this->rooms as $event) {
            $vEvent = new Event();
            $url = $this->userService->generateUrl($event, $user);
            $vEvent
                ->setOccurrence(new TimeSpan(
                        new DateTime($event->getStartWithTimeZone($user)->setTimeZone($timeZone), true),
                        new DateTime($event->getEndwithTimeZone($user)->setTimeZone($timeZone), true)
                    )
                )
                ->setSummary($event->getName())
                ->setDescription($event->getName() .
                    "\n" . $event->getAgenda() .
                    "\n" . $this->translator->trans('Hier beitreten') . ': ' . $url .
                    "\n" . $this->translator->trans('Organisator') . ': ' . $event->getModerator()->getFirstName() . ' ' . $event->getModerator()->getLastName())
                ->setLocation(new Location('Jitsi Meet-Konferenz'));

            $alarmInterval = new \DateInterval('PT10M');
            $alarmInterval->invert = 1;
            $vEvent->addAlarm(
                new \Eluceo\iCal\Domain\ValueObject\Alarm(new \Eluceo\iCal\Domain\ValueObject\Alarm\AudioAction(),
                    new \Eluceo\iCal\Domain\ValueObject\Alarm\RelativeTrigger($alarmInterval)
                )
            );
            $cal->addEvent($vEvent);

        }
        $componentFactory = new CalendarFactory();
        $value = $componentFactory->createCalendar($cal);
        return $value;
    }

    /**
     * @return mixed
     */
    public function getRooms()
    {
        return $this->rooms;
    }

    /**
     * @param mixed $rooms
     */
    public function setRooms($rooms): void
    {
        $this->rooms = array_values($rooms);

    }

}
