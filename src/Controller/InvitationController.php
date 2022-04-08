<?php

namespace App\Controller;

use App\Entity\User;
use App\Helper\JitsiAdminController;
use App\Service\InviteService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class InvitationController extends JitsiAdminController
{
    /**
     * @Route("/login/invitationAccept/{id}", name="invitation_accept")
     * @ParamConverter("user", class="App\Entity\User",options={"mapping": {"id": "registerId"}})
     */
    public function index(InviteService $inviteService, User $user, Request $request): Response
    {

        $inviteService->connectUserWithEmail($user,$this->getUser());
        return $this->redirectToRoute('dashboard');
    }
}
