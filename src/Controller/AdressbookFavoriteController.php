<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\adressbookFavoriteService\AdressbookFavoriteService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdressbookFavoriteController extends AbstractController
{
    public function __construct(
        private AdressbookFavoriteService       $adressbookFavoriteService,
        private TranslatorInterface             $translator,
        private readonly EntityManagerInterface $entityManager,
    )
    {
    }

    #[Route('/room/adressbook/favorite/{userId}', name: 'app_adressbook_favorite')]
    public function index($userId): Response
    {
        $userToAdd = $this->entityManager->getRepository(User::class)->findOneBy(['uid' => $userId]);

        if (!$userToAdd) {
            $this->addFlash('danger', $this->translator->trans('Fehler, Der User wurde nicht gefunden'));
            return $this->redirectToRoute('dashboard');
        }
        $res = $this->adressbookFavoriteService->userFavorite($this->getUser(), $userToAdd);
        $this->addFlash($res[0], $res[1]);
        return $this->redirectToRoute('dashboard');
    }

    #[Route('/room/adressbook/favorite-ajax/{userId}', name: 'app_adressbook_favorite_ajax', methods: ['POST'])]
    public function favoriteAjax(TranslatorInterface $translator, $userId): Response
    {
        $userToAdd = $this->entityManager->getRepository(User::class)->findOneBy(['uid' => $userId]);

        if (!$userToAdd) {
            return new JsonResponse(['error' => $translator->trans('Nicht gefunden')], 404);
        }
        $this->adressbookFavoriteService->userFavorite($this->getUser(), $userToAdd);
        return new JsonResponse(['ok' => true]);
    }
}
