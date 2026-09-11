<?php

namespace App\Controller;

use App\Entity\AddressGroup;
use App\Entity\Rooms;
use App\Entity\User;
use App\Helper\JitsiAdminController;
use App\Service\FavoriteService;
use App\Service\ParticipantSearchService;
use App\Service\RepeaterService;
use App\Service\RoomAddService;
use App\Service\Theme\ThemeService;
use App\Service\UserCreatorService;
use App\Service\UserService;
use App\UtilsHelper;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ParticipantController extends JitsiAdminController
{

    #[Route(path: '/room/participant/search', name: 'search_participant')]
    public function index(Request $request, ParticipantSearchService $participantSearchService, UserCreatorService $userCreatorService): Response
    {
        $string = $request->get('search');
        $string = strtolower($string);
        $user = $this->doctrine->getRepository(User::class)->findMyUserByIndex($string, $this->getUser());
        $group = $this->doctrine->getRepository(AddressGroup::class)->findMyAddressBookGroupsByName($string, $this->getUser());

        $res = [];
        if ($userCreatorService->doAllowUserCreation()) {
            $res['user'] = $participantSearchService->generateUserwithEmptyUser($user, $string);
        } else {
            $res['user'] = $participantSearchService->generateUserwithoutEmptyUser($user);
        }
        $res['group'] = $participantSearchService->generateGroup($group);
        return new JsonResponse($res);
    }

    #[Route(path: '/room/participant/add_single/{room}', name: 'room_add_user_single', methods: "POST")]
    public function roomAddUserSingle(Request $request, RoomAddService $roomAddService, Rooms $room, RepeaterService $repeaterService): JsonResponse
    {
        $invalidMember = [];
        $validMember=[];
        $validUser = new ArrayCollection();
        if (!UtilsHelper::isAllowedToOrganizeRoom($this->getUser(), $room)) {
            $this->addFlash('danger', $this->translator->trans('Keine Berechtigung'));
            return new JsonResponse(['error' => true]);
        }

        $newParticipant = json_decode($request->getContent(),true);
        if (isset($newParticipant['participant'])){
            $newParticipant = $newParticipant['participant'];
            $this->logger->debug('Participants found in cont send to add new participants',$newParticipant);
        }else{
            $this->logger->error('No participant entry in request for adding user', $newParticipant);
            return new JsonResponse(['error' => true]);
        }

        foreach ($newParticipant as $data) {
            try {
                $tmpUSer = $roomAddService->createSingleParticipantAndAddtoRoom($data, $this->getUser(), $room);
                if ($tmpUSer){
                    $validUser->add($tmpUSer);
                }
                $validMember[] = $data;
            } catch (\Exception) {
                $invalidMember[] = $data;
            }

        }
        if ($room->getRepeater()) {
            $this->logger->debug('We add users to a series');
            //here the users are added to the series. before the users are only added to the prototype room
            $repeaterService->addUserRepeat($room->getRepeater());
            try {
                $repeaterService->sendEMail($room->getRepeater(), 'email/repeaterNew.html.twig', $this->translator->trans('Eine neue Serienvideokonferenz wurde erstellt'), ['room' => $room->getRepeater()->getPrototyp()], 'REQUEST', $validUser->toArray());
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }

        }

        return new JsonResponse(['invalidMember' => $invalidMember,'validMember'=>$validMember]);
    }

    #[Route(path: '/room/participant/past', name: 'room_past_user')]
    public function roompastUser(Request $request, ThemeService $themeService)
    {

        $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(['id' => $request->get('room')]);
        if (!UtilsHelper::isAllowedToOrganizeRoom($this->getUser(), $room) && $themeService->getApplicationProperties('LAF_SHOW_PARTICIPANTS_ON_PARTICIPANTS') === 0) {
            $this->addFlash('danger', $this->translator->trans('Keine Berechtigung'));
            return $this->redirectToRoute('dashboard');
        }
        $title = $this->translator->trans('Teilnehmer');
        return $this->render('room/attendeeModalPast.twig', ['title' => $title, 'room' => $room]);
    }


    #[Route(path: '/room/participant/remove', name: 'room_user_remove')]
    public function roomUserRemove(Request $request, RoomAddService $roomAddService)
    {

        $room = $this->doctrine->getRepository(Rooms::class)->findOneBy(['id' => $request->get('room')]);
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['id' => $request->get('user')]);
        if ($user !== $this->getUser() && !UtilsHelper::isAllowedToOrganizeRoom($this->getUser(), $room)) {
            $this->addFlash('danger', 'Keine Berechtigung');
            return $this->redirectToRoute('dashboard');
        }

        if ($user === null) {
            return new JsonResponse(['error' => false, 'toast' => true, 'message' => $this->translator->trans('Teilnehmer gelöscht'), 'color' => 'success']);
        }

        $roomAddService->removeUserFromRoom($user, $room);
        return new JsonResponse(['error' => false, 'toast' => true, 'message' => $this->translator->trans('Teilnehmer gelöscht'), 'color' => 'success']);
    }



    #[Route(path: '/room/participant/resend', name: 'room_user_resend')]
    public function roomUserResend(Request $request, UserService $userService, RoomAddService $roomAddService)
    {
        $room = $this->doctrine->getRepository(Rooms::class)->findOneBy(['uidReal' => $request->get('room')]);
        if (!UtilsHelper::isAllowedToOrganizeRoom($this->getUser(), $room)) {
            if ($this->wantsJson($request)) {
                return new JsonResponse([
                    'error' => true,
                    'message' => $this->translator->trans('Keine Berechtigung'),
                    'color' => 'danger',
                ], Response::HTTP_FORBIDDEN);
            }
            $this->addFlash('danger', $this->translator->trans('Keine Berechtigung'));
            return $this->redirectToRoute('dashboard');
        }
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['id' => $request->get('user')]);
        if ($user === null || !in_array($room, $user->getRooms()->toArray())) {
            if ($this->wantsJson($request)) {
                return new JsonResponse([
                    'error' => true,
                    'message' => $this->translator->trans('Keine Berechtigung'),
                    'color' => 'danger',
                ], Response::HTTP_FORBIDDEN);
            }
            $this->addFlash('danger', $this->translator->trans('Keine Berechtigung'));
            return $this->redirectToRoute('dashboard');
        }
        $userService->addUser($user, $room);
        if ($this->wantsJson($request)) {
            return new JsonResponse([
                'error' => false,
                'toast' => true,
                'message' => $this->translator->trans('participant.resend.invitation.sucess'),
                'color' => 'success',
            ]);
        }
        $this->addFlash('success', $this->translator->trans('participant.resend.invitation.sucess'));
        return $this->redirectToRoute('dashboard');
    }

    /**
     * Adds many invitees from a textarea (one email or username per line) and returns
     * a translated summary. This is the JSON pendant of the former bulk invite form
     * submission used by the React participant management modal.
     */
    #[Route(path: '/room/participant/add_bulk/{room}', name: 'room_add_user_bulk', methods: ['POST'])]
    public function roomAddUserBulk(Request $request, RoomAddService $roomAddService, Rooms $room): JsonResponse
    {
        if (!UtilsHelper::isAllowedToOrganizeRoom($this->getUser(), $room)) {
            return new JsonResponse([
                'error' => true,
                'message' => $this->translator->trans('Keine Berechtigung'),
                'color' => 'danger',
            ], Response::HTTP_FORBIDDEN);
        }

        $payload = json_decode($request->getContent(), true);
        $member = trim((string) ($payload['member'] ?? ''));
        if ($member === '') {
            return new JsonResponse([
                'error' => true,
                'message' => $this->translator->trans('Fehler'),
                'color' => 'danger',
            ], Response::HTTP_BAD_REQUEST);
        }

        $falseEmail = $roomAddService->createParticipants($member, $room, $this->getUser());
        if (count($falseEmail) > 0) {
            $emails = implode(', ', $falseEmail);
            $snack = $this->translator->trans(
                'Einige Teilnehmer eingeladen. {emails} ist/sind nicht korrekt und können nicht eingeladen werden',
                ['{emails}' => $emails]
            );
            return new JsonResponse(['error' => false, 'message' => $snack, 'color' => 'warning']);
        }

        return new JsonResponse([
            'error' => false,
            'message' => $this->translator->trans('Teilnehmer wurden eingeladen'),
            'color' => 'success',
        ]);
    }
}
