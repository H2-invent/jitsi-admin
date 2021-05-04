<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\User;
use App\Service\UserService;
use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Event;
use Eluceo\iCal\Property\Event\Organizer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IcalController extends AbstractController
{
    /**
     * @Route("/ical/{id}", name="ical")
     * @ParamConverter("user", class="App\Entity\User",options={"mapping": {"id": "uid"}})
     */
    public function index(User $user, UserService $userService): Response
    {
        $events = $this->getDoctrine()->getRepository(Rooms::class)->findRoomsInFuture($user);
        $vCalendar = new Calendar('Jitsi Admin');
        foreach ($events as $event) {
            $vEvent = new Event();
            $url = $userService->generateUrl($event, $user);
            $vEvent
                ->setDtStart($event->getStart())
                ->setDtEnd($event->getEnddate())
                ->setSummary($event->getName())
                ->setDescription($event->getName() . "\n" . $event->getAgenda() . "\n" . $url)
                ->setLocation('Jitsi Meet-Konferenz')
                ->setOrganizer(new Organizer($event->getModerator()->getEmail()));

            $vCalendar->addComponent($vEvent);
        }

        // 5. Output
        $response = new Response();
        $response->headers->set('Content-Type', 'text/calendar; charset=utf-8');
        $response->headers->set('Content-Disposition', 'inline; filename="cal.ics"');
        $response->setContent($vCalendar);
        return $response;
    }
}
