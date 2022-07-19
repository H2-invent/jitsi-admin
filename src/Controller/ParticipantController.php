<?php

namespace App\Controller;

use App\Entity\AddressGroup;
use App\Entity\Rooms;
use App\Entity\User;
use App\Form\Type\NewMemberType;
use App\Helper\JitsiAdminController;
use App\Service\ParticipantSearchService;
use App\Service\RoomAddService;
use App\Service\ThemeService;
use App\Service\UserService;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use function GuzzleHttp\Psr7\str;

class ParticipantController extends JitsiAdminController
{


    /**
     * @Route("/room/participant/search", name="search_participant")
     */
    public function index(Request $request, ParticipantSearchService $participantSearchService): Response
    {
        $string = $request->get('search');
        $string = strtolower($string);
        $user = $this->doctrine->getRepository(User::class)->findMyUserByIndex($string, $this->getUser());
        $group = $this->doctrine->getRepository(AddressGroup::class)->findMyAddressBookGroupsByName($string, $this->getUser());

        $res = array();
        if($this->parameterBag->get('strict_allow_user_creation') == 1){
            $res['user'] = $participantSearchService->generateUserwithEmptyUser($user,$string);
        }else{
            $res['user'] = $participantSearchService->generateUserwithoutEmptyUser($user);
        }
        $res['group'] = $participantSearchService->generateGroup($group);
        return new JsonResponse($res);
    }

    /**
     * @Route("/room/participant/add", name="room_add_user")
     */
    public function roomAddUser(Request $request, RoomAddService $roomAddService)
    {
        $newMember = array();
        $room = $this->doctrine->getRepository(Rooms::class)->findOneBy(['id' => $request->get('room')]);
        if ($room->getModerator() !== $this->getUser()) {
            $this->addFlash('danger', $this->translator->trans('Keine Berechtigung'));
            return $this->redirectToRoute('dashboard');
        }
        $form = $this->createForm(NewMemberType::class, $newMember, ['action' => $this->generateUrl('room_add_user', ['room' => $room->getId()])]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {


            $newMembers = $form->getData();
            $falseEmail = [];
            $falseEmail = array_merge(
                $roomAddService->createParticipants($newMembers['member'], $room),
                $roomAddService->createModerators($newMembers['moderator'], $room)
            );

            if (sizeof($falseEmail) > 0) {
                $emails = implode(", ", $falseEmail);
                $snack = $this->translator->trans("Einige Teilnehmer eingeladen. {emails} ist/sind nicht korrekt und können nicht eingeladen werden", array('{emails}' => $emails));
            } else {
                $snack = $this->translator->trans('Teilnehmer wurden eingeladen');
            }
            $this->addFlash('success', $snack);
            return $this->redirectToRoute('dashboard');
        }

        $title = $this->translator->trans('Teilnehmer verwalten');

        return $this->render('room/attendeeModal.twig', array('form' => $form->createView(), 'title' => $title, 'room' => $room));
    }

    /**
     * @Route("/room/participant/past", name="room_past_user")
     */
    public function roompastUser(Request $request,ThemeService $themeService)
    {

        $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(['id' => $request->get('room')]);
        if ($room->getModerator() !== $this->getUser() && $themeService->getApplicationProperties('LAF_SHOW_PARTICIPANTS_ON_PARTICIPANTS') === 0) {
            $this->addFlash('danger', $this->translator->trans('Keine Berechtigung'));
            return $this->redirectToRoute('dashboard');
        }
        $title = $this->translator->trans('Teilnehmer');
        return $this->render('room/attendeeModalPast.twig', array('title' => $title, 'room' => $room));
    }


    /**
     * @Route("/room/participant/remove", name="room_user_remove")
     */
    public
    function roomUserRemove(Request $request, UserService $userService, RoomAddService $roomAddService)
    {

        $room = $this->doctrine->getRepository(Rooms::class)->findOneBy(['id' => $request->get('room')]);
        $repeater = false;

        if ($room->getRepeater()) {
            $repeater = true;
        }
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['id' => $request->get('user')]);
        $snack = 'Keine Berechtigung';
        if ($room->getModerator() === $this->getUser() || $user === $this->getUser()) {
            if (!$repeater) {
                $room->removeUser($user);
                $em = $this->doctrine->getManager();
                $em->persist($room);
                $em->flush();
                $userService->removeRoom($user, $room);
            } else {
                $roomAddService->removeUserFromRoom($user, $room);
            }

            $snack = $this->translator->trans('Teilnehmer gelöscht');
        }
        $this->addFlash('success', $snack);
        return $this->redirectToRoute('dashboard');
    }
    /**
     * @Route("/room/participant/resend", name="room_user_resend")
     */
    public
    function roomUserResend(Request $request, UserService $userService, RoomAddService $roomAddService)
    {
        $room = $this->doctrine->getRepository(Rooms::class)->findOneBy(array('uidReal'=>$request->get('room')));
        if ($room->getModerator() !== $this->getUser()) {
            $this->addFlash('danger', $this->translator->trans('Keine Berechtigung'));
            return $this->redirectToRoute('dashboard');
        }
        $user = $this->doctrine->getRepository(User::class)->findOneBy(array('id'=>$request->get('user')));
        if(!in_array($room,$user->getRooms()->toArray())){
            $this->addFlash('danger', $this->translator->trans('Keine Berechtigung'));
            return $this->redirectToRoute('dashboard');
        }
        $userService->addUser($user,$room);
        $this->addFlash('success', $this->translator->trans('participant.resend.invitation.sucess'));
        return $this->redirectToRoute('dashboard');
    }
}
