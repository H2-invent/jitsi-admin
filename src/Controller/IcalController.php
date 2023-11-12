<?php

namespace App\Controller;

use App\Entity\User;
use App\Helper\JitsiAdminController;
use App\Service\IcalService;
use App\Service\LicenseService;
use App\Service\UserService;
use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Event;
use Eluceo\iCal\Property\Event\Organizer;

use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IcalController extends JitsiAdminController
{
    /**
     * @Route("/ical/{id}", name="ical")

     */
    public function index(
        #[MapEntity(mapping: ['id' => 'uid'])] User $user,
        UserService $userService,
        LicenseService $licenseService,
        IcalService $icalService,
    ): Response
    {

        $response = new Response();
        $response->headers->set('Content-Type', 'text/calendar; charset=utf-8');
        $response->headers->set('Content-Disposition', 'inline; filename="cal.ics"');
        $response->setContent($icalService->getIcal($user));

        return $response;
    }
}
