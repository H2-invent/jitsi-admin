<?php


namespace App\Service;


use App\Entity\Repeat;
use App\Entity\Rooms;
use App\Entity\User;
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
            $value = $this->getIcalString($this->user);

        } else {
            $cache = new FilesystemAdapter();
            $value = $cache->get('ical_' . $user->getUid(), function (ItemInterface $item) {
                $item->expiresAfter(900);
                return $this->getIcalString($this->user);
            });
        }

        return $value;

    }

    private function getIcalString(User $user)
    {
        $events = $this->em->getRepository(Rooms::class)->findRoomsFutureAndPast($user, '-1 month');

        $cal = new Calendar();

        $timeZone = TimeZoneService::getTimeZone($user);
        $start = new \DateTime();
        $end = new \DateTime();
        if (sizeof($events) > 1) {
            $start = $events[0]->getStart()->modify('-1year');
            $end = $events[sizeof($events) - 1]->getEndDate()->modify('+1year');
            $timeTransitions = $timeZone->getTransitions($start->getTimeStamp(), $end->getTimeStamp());
            $timeTransitions = array_splice($timeTransitions,1);
            $cout = 0;
            foreach ($timeTransitions as $data) {
                try {
                    $cal->addTimeZone(
                        TimeZone::createFromPhpDateTimeZone(
                            $timeZone,
                            new \DateTime($data['time']),
                            new \DateTime($timeTransitions[$cout+1]['time'])
                        )
                    );
                    $cout++;
                }catch (\Exception $exception){
                    break;
                }

            }
        }
        foreach ($events as $event) {
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


    public function getIcalStringfromRepeater(Repeat $repeat, User $user)
    {
        $tmp = $repeat->getRooms()->toArray();
        $events = array();
        foreach ($tmp as $data) {
            $events[] = $data;
        }
        $cal = new Calendar();
        $timeZone = new \DateTimeZone('Europe/Berlin');
        $start = new \DateTime();
        $end = new \DateTime();
        if (sizeof($events) > 1) {
            $start = $events[0]->getStart();
            $end = $events[sizeof($events) - 1]->getEndDate();
        }
        $cal->addTimeZone(
            TimeZone::createFromPhpDateTimeZone(
                $timeZone,
                $start,
                $end
            )
        );
        foreach ($events as $event) {
            $vEvent = new Event();
            $url = $this->userService->generateUrl($event, $user);
            $vEvent
                ->setOccurrence(new TimeSpan(
                        new DateTime($event->getStart(), true),
                        new DateTime($event->getEndDate(), true)
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
}
