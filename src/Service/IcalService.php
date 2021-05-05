<?php


namespace App\Service;


use App\Entity\Rooms;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Event;
use Eluceo\iCal\Property\Event\Organizer;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

class IcalService
{
    private $licenseService;
    private $em;
    private $userService;
    private $user;
    public function __construct(LicenseService $licenseService, EntityManagerInterface $entityManager,UserService $userService)
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
        if($isEnterprise){
            $value = $this->getIcalString($this->user);
        }else {
            $cache = new FilesystemAdapter();
            $value = $cache->get('ical_' . $user->getUid(), function (ItemInterface $item) {
                $item->expiresAfter(900);
                return $this->getIcalString($this->user)->render();
            });
        }

    return $value;

    }
    private function getIcalString(User  $user):Calendar{
        $events = $this->em->getRepository(Rooms::class)->findRoomsFutureAndPast($user, '-1 month');
        $vCalendar = new Calendar('Jitsi Admin');
        foreach ($events as $event) {
            $vEvent = new Event();
            $url = $this->userService->generateUrl($event, $user);
            $vEvent
                ->setDtStart($event->getStart())
                ->setDtEnd($event->getEnddate())
                ->setSummary($event->getName())
                ->setDescription($event->getName() . "\n" . $event->getAgenda() . "\n" . $url)
                ->setLocation('Jitsi Meet-Konferenz')
                ->setOrganizer(new Organizer($event->getModerator()->getEmail()));

            $vCalendar->addComponent($vEvent);
        }
        return $vCalendar;
    }
}