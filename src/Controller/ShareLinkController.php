<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\Subscriber;
use App\Entity\User;
use App\Entity\Waitinglist;
use App\Form\Type\PublicRegisterType;
use App\Helper\JitsiAdminController;
use App\Service\PexelService;
use App\Service\RoomService;
use App\Service\SubcriptionService;
use App\Service\UserService;
use App\UtilsHelper;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Json;
use Symfony\Contracts\Translation\TranslatorInterface;

use function Symfony\Component\String\s;

class ShareLinkController extends JitsiAdminController
{
    /**
     * @Route("/room/share/link/{id}", name="share_link")
     */
    public function index(
        Rooms $rooms
    ): Response
    {
        if (!$rooms || !UtilsHelper::isAllowedToOrganizeRoom($this->getUser(), $rooms) || $rooms->getPublic() != true) {
            throw new NotFoundHttpException('Not found');
        }
        return $this->render('share_link/__shareLinkModal.html.twig', ['room' => $rooms]);
    }

    /**
     * @Route("/room/share/link/accetwaitinglist/{id}", name="accept_waitingList")
     */
    public function waitinglistAccept(
        Waitinglist $waitinglist,
        SubcriptionService $subcriptionService,
    ): Response
    {
        if (UtilsHelper::isAllowedToOrganizeRoom($this->getUser(), $waitinglist->getRoom())) {
            $subcriptionService->createUserRoom($waitinglist->getUser(), $waitinglist->getRoom());
            $em = $this->doctrine->getManager();
            $em->remove($waitinglist);
            $em->flush();
            return new JsonResponse(['error' => false]);
        }
        return new JsonResponse(['error' => true]);
    }

    /**
     * @Route("/subscribe/self/{uid}", name="public_subscribe_participant")
     */
    public function participants($uid, Request $request, SubcriptionService $subcriptionService, TranslatorInterface $translator, PexelService $pexelService): Response
    {
        $moderator = false;
        $rooms = $this->doctrine->getRepository(Rooms::class)->findOneBy(['uidParticipant' => $uid, 'public' => true]);
        if (!$rooms) {
            $rooms = $this->doctrine->getRepository(Rooms::class)->findOneBy(['uidModerator' => $uid, 'public' => true]);
            if ($rooms) {
                $moderator = true;
            }
        }
        if (!$rooms || $rooms->getModerator() === null) {
            $this->addFlash('danger', $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.'));
            return $this->redirectToRoute('join_index_no_slug');
        }

        $data = ['email' => ''];
        $form = $this->createForm(PublicRegisterType::class, $data);
        $form->handleRequest($request);
        $snack = $translator->trans('Bitte geben Sie ihre Daten ein');
        $color = 'success';
        $server = null;
        if ($rooms->getMaxParticipants() && (sizeof($rooms->getUser()->toArray()) >= $rooms->getMaxParticipants())) {
            $snack = $translator->trans('Die maximale Teilnehmeranzahl ist bereits erreicht.');
            $color = 'danger';
        }
        if ($rooms->getMaxParticipants() && (sizeof($rooms->getUser()->toArray()) >= $rooms->getMaxParticipants()) && $rooms->getWaitinglist() == true) {
            $snack = $translator->trans('Die maximale Teilnehmeranzahl ist bereits erreicht. Aber sie können sich auf die Warteliste einschreiben.');
            $color = 'warning';
        }
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $res = $subcriptionService->subscripe($data, $rooms, $moderator);
            $snack = $res['text'];
            $color = $res['color'];
            if (!$res['error']) {
                $this->addFlash($color, $snack);
                return $this->redirectToRoute('public_subscribe_participant', ['uid' => $uid]);
            }
        }
        $server = $rooms->getServer();
        $this->addFlash($color, $snack);
        return $this->render(
            'share_link/subscribe.html.twig',
            [
                'form' => $form->createView(),
                'server' => $server,
                'room' => $rooms,
            ]
        );
    }


    /**
     * @Route("/subscribe/optIn/{uid}", name="public_subscribe_doupleOptIn")
     */
    public function doupleoptin($uid, SubcriptionService $subcriptionService, TranslatorInterface $translator, UserService $userService, PexelService $pexelService): Response
    {
        $subscriber = $this->doctrine->getRepository(Subscriber::class)->findOneBy(['uid' => $uid]);
        $res = $subcriptionService->acceptSub($subscriber);
        $server = null;
        if ($subscriber) {
            $server = $subscriber->getRoom()->getServer();
        }

        $message = $res['message'];
        $title = $res['title'];
        if ($subscriber && $subscriber->getRoom()->getScheduleMeeting()) {
            return $this->redirectToRoute('schedule_public_main', ['scheduleId' => $subscriber->getRoom()->getSchedulings()[0]->getUid(), 'userId' => $subscriber->getUser()->getUid()]);
        }
        return $this->render('share_link/subscribeSuccess.html.twig', ['server' => $server, 'message' => $message, 'title' => $title]);
    }
}
