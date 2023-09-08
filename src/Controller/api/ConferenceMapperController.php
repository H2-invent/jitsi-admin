<?php

namespace App\Controller\api;

use App\Entity\CallerRoom;
use App\Helper\JitsiAdminController;
use App\Service\api\ConferenceMapperService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ConferenceMapperController extends JitsiAdminController
{
    public function __construct(
        ManagerRegistry                 $managerRegistry,
        TranslatorInterface             $translator,
        LoggerInterface                 $logger,
        ParameterBagInterface           $parameterBag,
        private ConferenceMapperService $conferenceMapperService
    )
    {
        parent::__construct($managerRegistry, $translator, $logger, $parameterBag);
    }

    #[Route('/api/v1/conferenceMapper', name: 'app_conference_mapper', methods: 'GET')]
    public function index(Request $request): Response
    {
        return new JsonResponse(
            $this->conferenceMapperService->checkConference(
                callerRoom: $this->doctrine->getRepository(CallerRoom::class)->findOneBy(['callerId' => $request->get('confid')]),
                apiKey: $request->headers->get('Authorization'),
                callerId: $request->get('callerid')
            )
        );
    }
}
