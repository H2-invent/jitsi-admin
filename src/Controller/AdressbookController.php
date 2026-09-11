<?php

namespace App\Controller;

use App\Entity\User;
use App\Helper\JitsiAdminController;
use App\Service\adressbookFavoriteService\AdressbookFavoriteService;
use App\Service\Dashboard\AddressBookViewService;
use App\Service\Deputy\DeputyService;
use App\Service\UserCreatorService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdressbookController extends JitsiAdminController
{
    public function __construct(
        ManagerRegistry                   $managerRegistry,
        TranslatorInterface               $translator,
        LoggerInterface                   $logger,
        ParameterBagInterface             $parameterBag,
        private AdressbookFavoriteService $adressbookFavoriteService,
        private DeputyService             $deputyService,
        private UserCreatorService        $userCreatorService,
        private AddressBookViewService    $addressBookViewService,
    )
    {
        parent::__construct($managerRegistry, $translator, $logger, $parameterBag);
    }

    #[Route(path: '/room/adressbook/remove', name: 'adressbook_remove_user')]
    public function index(Request $request): Response
    {
        $user = $this->doctrine->getRepository(User::class)->find($request->get('id'));
        $myUser = $this->getUser();
        $myUser->removeAddressbook($user);
        $this->adressbookFavoriteService->removeFavorite($myUser, $user);
        $this->deputyService->removeDeputy($myUser, $user);
        $em = $this->doctrine->getManager();
        $em->persist($myUser);
        $em->flush();
        return $this->redirectToRoute('dashboard');
    }

    #[Route(path: '/room/adressbook/remove-ajax', name: 'adressbook_remove_user_ajax', methods: ['POST'])]
    public function removeAjax(Request $request, TranslatorInterface $translator): Response
    {
        $user = $this->doctrine->getRepository(User::class)->find($request->get('id'));
        if (!$user) {
            return new JsonResponse(['error' => $translator->trans('Nicht gefunden')], Response::HTTP_NOT_FOUND);
        }
        $myUser = $this->getUser();
        $myUser->removeAddressbook($user);
        $this->adressbookFavoriteService->removeFavorite($myUser, $user);
        $this->deputyService->removeDeputy($myUser, $user);
        $em = $this->doctrine->getManager();
        $em->persist($myUser);
        $em->flush();
        return new JsonResponse(['ok' => true]);
    }

    #[Route(path: '/room/adressbook/add-ajax', name: 'adressbook_add_user_ajax', methods: ['POST'])]
    public function addAjax(Request $request, TranslatorInterface $translator): Response
    {
        $email = trim($request->get('email', ''));
        if (!$email) {
            return new JsonResponse(['error' => $translator->trans('Bitte geben Sie eine E-Mail-Adresse ein.')], Response::HTTP_BAD_REQUEST);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['error' => $translator->trans('Bitte geben Sie eine gültige E-Mail-Adresse ein.')], Response::HTTP_BAD_REQUEST);
        }

        $myUser = $this->getUser();
        if ($email === $myUser->getEmail()) {
            return new JsonResponse(['error' => $translator->trans('Sie können sich nicht selbst hinzufügen.')], Response::HTTP_BAD_REQUEST);
        }

        $contact = $this->doctrine->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$contact && $this->userCreatorService->doAllowUserCreation()) {
            $contact = $this->userCreatorService->createUser($email, $email, '', '');
        }

        if ($contact) {
            if ($myUser->getAddressbook()->contains($contact)) {
                return new JsonResponse(['error' => $translator->trans('Der Kontakt ist bereits im Adressbuch.')], Response::HTTP_BAD_REQUEST);
            }
            $myUser->addAddressbook($contact);
        } else {
            return new JsonResponse(['error' => $translator->trans('Kein Benutzer mit dieser E-Mail-Adresse gefunden.')], Response::HTTP_NOT_FOUND);
        }

        $em = $this->doctrine->getManager();
        $em->persist($myUser);
        $em->flush();

        return new JsonResponse([
            'ok' => true,
            'contact' => $this->addressBookViewService->serializeContact($myUser, $contact),
        ]);
    }

    #[Route(path: '/room/adressbook/new-contact', name: 'adressbook_new_contact')]
    public function newContactModal(): Response
    {
        return $this->render('addressbook/__newContactModal.html.twig');
    }
}
