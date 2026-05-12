<?php

namespace App\Controller;

use App\Entity\User;
use App\Helper\JitsiAdminController;
use App\Service\IcalService;

use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IcalController extends JitsiAdminController
{
    
    #[Route(path: '/ical/{id}', name: 'ical')]
    public function index(
        #[MapEntity(mapping: ['id' => 'uid'])] User $user,
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
