<?php

namespace App\Controller;

use App\Entity\User;
use App\Helper\JitsiAdminController;
use App\Service\InviteService;

use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InvitationController extends JitsiAdminController
{
    /**
     * @Route("/login/invitationAccept/{id}", name="invitation_accept")
     */
    public function index(
        #[MapEntity(mapping: ['id' => 'registerId'])]
        User $user,
        InviteService $inviteService,
        Request $request): Response
    {

        $inviteService->connectUserWithEmail($user, $this->getUser());
        return $this->redirectToRoute('dashboard');
    }
}
