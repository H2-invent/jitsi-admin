<?php


namespace App\Service;


use App\Entity\Rooms;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Domain\Entity\Event;
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

class IcalService
{
    private $licenseService;
    private $em;
    private $userService;
    private $user;

    public function __construct(LicenseService $licenseService, EntityManagerInterface $entityManager, UserService $userService)
    {
        $this->licenseService = $licenseService;
        $this->em = $entityManager;
        $this->userService = $userService;
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
        $eventA = array();
        foreach ($events as $event) {
            $vEvent = new Event();
            $url = $this->userService->generateUrl($event, $user);
            $vEvent
                ->setOccurrence(new TimeSpan(new DateTime(
                    $event->getStart(), false),
                    new DateTime($event->getEndDate(), false)))
                ->setSummary($event->getName())
                ->setDescription($event->getName() . "\n" . $event->getAgenda() . "\n" . $url)
                ->setLocation(new Location('Jitsi Meet-Konferenz'))
                ->setOrganizer(new Organizer(new EmailAddress($event->getModerator()->getEmail())));
            $alarmInterval = new \DateInterval('PT10M');
            $alarmInterval->invert = 1;
            $vEvent->addAlarm(
                new \Eluceo\iCal\Domain\ValueObject\Alarm(new \Eluceo\iCal\Domain\ValueObject\Alarm\AudioAction(),
                    new \Eluceo\iCal\Domain\ValueObject\Alarm\RelativeTrigger($alarmInterval)
                )
            );
            $eventA[] = $vEvent;
        }
        $componentFactory = new CalendarFactory();
        $value = $componentFactory->createCalendar(new Calendar($eventA));
        return $value;
    }
}