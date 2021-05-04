<?php

namespace App\Controller;

use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Event;
use Eluceo\iCal\Component\Timezone;
use Eluceo\iCal\Property\Event\Organizer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IcalController extends AbstractController
{
    /**
     * @Route("/ical", name="ical")
     */
    public function index(): Response
    {
        $date = new Date(\DateTimeImmutable::createFromFormat('Y-m-d', '2019-12-24'));
        $occurrence = new SingleDay($date);
        $event = new Event();
        $events = [
            $event->setOrganizer(new Organizer("Andreas"))
                ->setSummary('Christmas Eve')
                ->setDtStamp($this->formatTimestamp('now')),
        ];

        $calendar = new Calendar($events);
        $calendar->addTimeZone(Timezone::createFromPhpDateTimeZone(new \DateTimeZone('Europe/Berlin')));


        // 3. Transform domain entity into an iCalendar component
        $componentFactory = (new \Eluceo\iCal\Presentation\Factory\CalendarFactory())->createCalendar($calendar);
        $calendarComponent = $componentFactory->createCalendar($calendar);

        // 5. Output
        $response = new Response();
        $response->headers->set('Content-Type', 'text/calendar; charset=utf-8');
        $response->headers->set('Content-Disposition', 'inline; filename="cal.ics"');
        $response->setContent($calendar->render());
        return $response;
    }
}
