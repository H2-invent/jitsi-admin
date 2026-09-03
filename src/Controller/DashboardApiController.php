<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\User;
use App\Helper\JitsiAdminController;
use App\Service\Dashboard\DashboardViewService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * JSON API used by the React dashboard application.
 *
 * Every endpoint re-validates the current user and the referenced resources
 * server-side; the React UI only consumes the structured responses.
 */
class DashboardApiController extends JitsiAdminController
{
    public function __construct(
        ManagerRegistry $managerRegistry,
        TranslatorInterface $translator,
        LoggerInterface $logger,
        ParameterBagInterface $parameterBag,
        private readonly DashboardViewService $dashboardViewService,
    ) {
        parent::__construct($managerRegistry, $translator, $logger, $parameterBag);
    }

    /**
     * Live status (open/closed/occupants) for the rooms currently displayed on the
     * dashboard. The client sends the room ids it renders as a comma separated list;
     * only ids the user may actually see are answered, so arbitrary rooms cannot be
     * probed.
     */
    #[Route(path: '/room/dashboard/api/occupants', name: 'dashboard_api_occupants', methods: ['GET'])]
    public function occupants(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $raw = (string) $request->query->get('ids', '');
        $ids = array_values(array_filter(array_map('intval', explode(',', $raw))));

        if (count($ids) > 500) {
            return new JsonResponse(['error' => 'too_many_rooms'], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($this->dashboardViewService->buildStatusResponse($user, $ids));
    }

    /**
     * Next page of past conferences (infinite scroll).
     */
    #[Route(path: '/room/dashboard/api/rooms/past', name: 'dashboard_api_rooms_past', methods: ['GET'])]
    public function pastRooms(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $offset = max(0, (int) $request->get('offset', 0));
        if ($offset > 10000) {
            return new JsonResponse(['error' => 'offset_too_large'], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($this->dashboardViewService->buildPastPage($user, $offset));
    }

    /**
     * Toggles a room's favorite state and returns the updated favourite room list so
     * the React sidebar and star icons can be refreshed from the server truth.
     */
    #[Route(path: '/room/dashboard/api/favorite/toggle', name: 'dashboard_api_favorite_toggle', methods: ['POST'])]
    public function toggleFavorite(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $uid = $request->getPayload()->get('uid');
        $room = $this->doctrine->getRepository(Rooms::class)->findOneBy(['uidReal' => $uid]);
        if (!$room) {
            return new JsonResponse(['error' => $this->translator->trans('Fehler')], Response::HTTP_NOT_FOUND);
        }

        if (!in_array($user, $room->getUser()->toArray(), true)) {
            return new JsonResponse(['error' => $this->translator->trans('Fehler')], Response::HTTP_FORBIDDEN);
        }

        if ($room->getFavoriteUsers()->contains($user)) {
            $user->removeFavorite($room);
            $isFavorite = false;
        } else {
            $user->addFavorite($room);
            $isFavorite = true;
        }
        $this->doctrine->getManager()->persist($user);
        $this->doctrine->getManager()->flush();

        return new JsonResponse([
            'ok' => true,
            'isFavorite' => $isFavorite,
            'roomId' => $room->getId(),
            'favorites' => $this->dashboardViewService->buildFavoriteRooms($user),
        ]);
    }
}
