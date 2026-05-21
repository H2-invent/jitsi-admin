<?php

namespace App\Service;

use App\Entity\Rooms;
use App\Entity\User;
use App\Service\Jigasi\JigasiService;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Contracts\Translation\TranslatorInterface;

class IcalService
{

    private $em;
    private $userService;
    private $user;
    private $translator;
    private $rooms;
    private $jigasiService;

    public function __construct(TranslatorInterface $translator, EntityManagerInterface $entityManager, UserService $userService, JigasiService $jigasiService)
    {

        $this->em = $entityManager;
        $this->userService = $userService;
        $this->translator = $translator;
        $this->jigasiService = $jigasiService;
    }

    public function getIcal(User $user)
    {

        $this->user = $user;
        $server = $user->getServers();

        $value = "";
        $this->initRooms($user);
        $value = $this->getIcalString();


        return $value;
    }

    public
    function initRooms(User $user)
    {
        $this->rooms = $this->em->getRepository(Rooms::class)->findRoomsFutureAndPast($user, "-1 month");
        $this->rooms = array_values($this->rooms);
    }

    public
    function getIcalString()
    {
        $ics = new IcsService();
        foreach ($this->rooms as $event) {
            /**
             * @var Rooms $event
             */
            $url = $this->userService->generateUrl($event, $this->user);
            $description =
                $event->getName() .
                "\n" . $event->getAgenda() .
                "\n". $this->translator->trans("Hier beitreten") . ": " . $url .
                "\n" . $this->translator->trans("Organisator") . ": " . $event->getModerator()->getFirstName() . " " . $event->getModerator()->getLastName();

            if ($this->jigasiService->getRoomPin($event) && $this->jigasiService->getNumber($event)) {
                $description = $description . "\n\n\n" . $this->translator->trans("email.sip.text") . "\n";

                foreach ($this->jigasiService->getNumber($event) as $key => $value) {
                    foreach ($value as $data) {
                        $description = $description
                            . sprintf("(%s) %s %s: %s# (%s,,%s#)"."\n", $key, $data, $this->translator->trans("email.sip.pin"), $this->jigasiService->getRoomPin($event), $data, $this->jigasiService->getRoomPin($event));
                    }
                }
            }

            $ics->addEvent(
                [
                    "uid" => md5($event->getUid()) . "@" . parse_url($event->getHostUrl(), PHP_URL_HOST),
                    "location" => $this->translator->trans("meetling Konferenz"),
                    "description" => $description,
                    "dtstart" => $event->getStartUtc(),
                    "dtend" => $event->getEndDateUtc(),
                    "summary" => $event->getName(),
                    "sequence" => $event->getSequence(),
                    "organizer" => "MAILTO:" . $event->getModerator()->getEmail(),
                    "attendee" => $this->user->getEmail(),
                    "transp" => "OPAQUE",
                    "url" => $url,
                    "class" => "public"
                ]
            );
            $ics->setMethod("PUBLISH");

        }

        return $ics->toString();
    }

    /**
     * @return mixed
     */
    public
    function getRooms()
    {
        return $this->rooms;
    }

    /**
     * @param mixed $rooms
     */
    public
    function setRooms($rooms): void
    {
        $this->rooms = array_values($rooms);
    }
}
