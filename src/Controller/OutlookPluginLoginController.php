<?php

namespace App\Controller;

use App\Entity\Server;
use App\Entity\User;
use App\Service\CreateHttpsUrl;
use App\Service\PublicConference\PublicConferenceService;
use App\Service\ServerUserManagment;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/room/outlook/plugin', name: 'app_outlook_plugin')]
final class OutlookPluginLoginController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    )
    {

    }

    #[Route('/outlooklogin', name: 'login', methods: ['GET'])]
    public function login(): Response
    {
        /**@var \App\Entity\User $user * */
        $user = $this->getUser();

        if (!$user){
            return new JsonResponse(['error' => true,'reason'=>'No user loged in' ]);
        }
        $user->setOutlookPluginToken(null);
        if (!$user->getOutlookPluginToken()) {
            do {
                $token = md5(uniqid($user->getUid(), true));
                $search = $this->entityManager->getRepository(User::class)->findOneBy(['outlookPluginToken' => $token]);
            }while($search);

            $user->setOutlookPluginToken($token);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
        return new JsonResponse(['token' => $user->getOutlookPluginToken(),'name'=>$user->getFirstName().' '.$user->getLastName()]);
    }

}
