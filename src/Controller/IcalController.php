<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\User;
use App\Helper\JitsiAdminController;
use App\Service\IcalService;
use App\Service\LicenseService;
use App\Service\UserService;
use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Event;
use Eluceo\iCal\Property\Event\Organizer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\ItemInterface;

class IcalController extends JitsiAdminController
{
    /**
     * @Route("/ical/{id}", name="ical")
     * @ParamConverter("user", class="App\Entity\User",options={"mapping": {"id": "uid"}})
     */
    public function index(User $user, UserService $userService,LicenseService $licenseService, IcalService $icalService): Response
    {

        $response = new Response();
        $response->headers->set('Content-Type', 'text/calendar; charset=utf-8');
        $response->headers->set('Content-Disposition', 'inline; filename="cal.ics"');
        $response->setContent($icalService->getIcal($user));

        return $response;
    }
}
