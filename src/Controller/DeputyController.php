<?php

namespace App\Controller;

use App\Entity\User;
use App\Helper\JitsiAdminController;
use App\Service\Deputy\DeputyService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/room/deputy', name: 'app_deputy_')]
class DeputyController extends JitsiAdminController
{
    public function __construct(
        ManagerRegistry       $managerRegistry,
        TranslatorInterface   $translator,
        LoggerInterface       $logger,
        ParameterBagInterface $parameterBag,
        private DeputyService $deputyService
    )
    {
        parent::__construct($managerRegistry, $translator, $logger, $parameterBag);
    }

    #[Route('/toggle/{deputyUid}', name: 'add')]
    public function index($deputyUid): Response
    {
        $user = $this->getUser();
        $deputy = $this->doctrine->getRepository(User::class)->findOneBy(['uid' => $deputyUid]);
        if (!in_array($deputy, $user->getAddressbook()->toArray())) {
            $this->addFlash('danger', $this->translator->trans('Diese Aktion ist nicht erlaubt.'));
        } else {
            $res = $this->deputyService->toggleDeputy($user, $deputy);
            $this->addFlash('success', $res === DeputyService::$IS_DEPUTY ? $this->translator->trans('deputy.message.added') : $this->translator->trans('deputy.message.removed'));
        }

        return $this->redirectToRoute('dashboard');
    }
}
