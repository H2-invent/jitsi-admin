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

    /**
     * @deprecated Use the caller api instead: /api/v1/lobby/sip/room/{roomId} points to
     *             /api/v1/lobby/sip/open/{roomId} for rooms without a lobby, which returns the
     *             same payload. This endpoint is kept for existing asterisk configurations and
     *             will be removed in a future release.
     */
    #[Route('/api/v1/conferenceMapper', name: 'app_conference_mapper', methods: 'GET')]
    public function index(Request $request): Response
    {
        $this->logger->warning(
            'Deprecated: /api/v1/conferenceMapper was called. Use /api/v1/lobby/sip/open/{roomId} instead.',
            ['confid' => $request->get('confid')]
        );

        return new JsonResponse(
            $this->conferenceMapperService->checkConference(
                callerRoom: $this->doctrine->getRepository(CallerRoom::class)->findOneBy(['callerId' => $request->get('confid')]),
                apiKey: $request->headers->get('Authorization'),
                callerId: $request->get('callerid')
            )
        );
    }
}
