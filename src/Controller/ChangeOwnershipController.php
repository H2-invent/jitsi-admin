<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\User;
use App\Repository\RoomsUserRepository;
use App\Service\TransferOwnershipService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/room/ownership', name: 'room_change_ownership')]
class ChangeOwnershipController extends AbstractController
{
    public function __construct(
        private TransferOwnershipService $transferOwnershipService,
        private TranslatorInterface      $translator,
        private RoomsUserRepository      $roomsUserRepository,
    )
    {
    }

    #[Route('/{newOwner}/{roomId}', name: '_index')]
    public function index(
        Request $request,
        #[MapEntity(mapping: ['newOwner' => 'id'])]
        User  $newOwner,
        #[MapEntity(mapping: ['roomId' => 'id'])]
        Rooms $rooms,
    ): Response
    {
        $roomPermissions = $this->roomsUserRepository->findOneBy(['room'=>$rooms,'user'=>$newOwner]);
        if ($rooms->getModerator() === $this->getUser() && $roomPermissions && $roomPermissions->getModerator() && $newOwner->getKeycloakId()) {
            $this->transferOwnershipService->transferOwnership(newOwner: $newOwner, room: $rooms);
            if (in_array('application/json', $request->getAcceptableContentTypes(), true) || $request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'error' => false,
                    'toast' => true,
                    'message' => $this->translator->trans('transfer.room.success'),
                    'color' => 'success',
                ]);
            }
            $this->addFlash('success', $this->translator->trans('transfer.room.success'));
        }else{
            if (in_array('application/json', $request->getAcceptableContentTypes(), true) || $request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'error' => true,
                    'toast' => true,
                    'message' => $this->translator->trans('transfer.room.error'),
                    'color' => 'danger',
                ], Response::HTTP_FORBIDDEN);
            }
            $this->addFlash('danger', $this->translator->trans('transfer.room.error'));
        }

        return new RedirectResponse($this->generateUrl('dashboard'));
    }

}
