<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Form\Type\PublicConferenceType;
use App\Helper\JitsiAdminController;
use App\Service\ThemeService;
use App\Service\webhook\RoomStatusFrontendService;
use App\UtilsHelper;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class PublicConferenceController extends JitsiAdminController
{
    public function __construct(
        ManagerRegistry                      $managerRegistry,
        TranslatorInterface                  $translator,
        LoggerInterface                      $logger,
        ParameterBagInterface                $parameterBag,
        private ThemeService                 $themeService,
        private RequestStack                 $requestStack,
        private RoomStatusFrontendService $roomStatusFrontendService)
    {
        parent::__construct($managerRegistry, $translator, $logger, $parameterBag);
    }

    #[Route('/m', name: 'app_public_form')]
    public function index(Request $request): Response
    {
        if($this->themeService->getApplicationProperties('PUBLIC_SERVER')===0){
            return $this->redirectToRoute('dashboard');
        }
        $server = $this->doctrine->getRepository(Server::class)->find($this->themeService->getApplicationProperties('PUBLIC_SERVER'));
        $data = array('roomName' => UtilsHelper::readable_random_string(20));
        $form = $this->createForm(PublicConferenceType::class, $data);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $roomname = UtilsHelper::slugify($data['roomName']);
            $uid = md5($server->getUrl() . $roomname);
            $room = $this->doctrine->getRepository(Rooms::class)->findOneBy(array('uid' => $uid, 'moderator' => null));
            if (!$room) {
                $room = new Rooms();
                $room->setServer($server)
                    ->setUid($uid)
                    ->setName($roomname)
                    ->setDuration(0)
                    ->setSequence(0)
                    ->setUidReal(md5(uniqid()));
                if ($this->requestStack && $this->requestStack->getCurrentRequest()) {
                    $room->setHostUrl($this->requestStack->getCurrentRequest()->getSchemeAndHttpHost());
                }

                $em = $this->doctrine->getManager();
                $em->persist($room);
                $em->flush();
            }
            return $this->redirectToRoute('app_public_conference', array('confId' => $roomname));

        }
        return $this->render('public_conference/index.html.twig', [
            'form' => $form->createView(),
            'server' => $server
        ]);
    }

    #[Route('/m/{confId}', name: 'app_public_conference')]
    public function startMeeting($confId): Response
    {
        $server = $this->doctrine->getRepository(Server::class)->find($this->themeService->getApplicationProperties('PUBLIC_SERVER'));
        $uid = md5($server->getUrl() . $confId);
        $room = $this->doctrine->getRepository(Rooms::class)->findOneBy(array('uid' => $uid));
        $firstUser = $this->roomStatusFrontendService->isRoomCreated($room);
        return $this->render('start/index.html.twig', [
            'room' => $room,
            'user' => null,
            'name' => 'Jitsi-Fellower',
            'moderator'=>!$firstUser
        ]);
    }

}
