<?php

namespace App\Controller;

use App\Entity\AddressGroup;
use App\Entity\Rooms;
use App\Entity\User;
use App\Form\Type\NewMemberType;
use App\Service\RoomAddService;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use function GuzzleHttp\Psr7\str;

class ParticipantController extends AbstractController
{
    private $translator;
    private $parameterBag;

    public function __construct(TranslatorInterface $translator, ParameterBagInterface $parameterBag)
    {
        $this->translator = $translator;
        $this->parameterBag = $parameterBag;
    }

    /**
     * @Route("/room/participant/search", name="search_participant")
     */
    public function index(Request $request, TranslatorInterface $translator): Response
    {
        $string = $request->get('search');
        $user = $this->getDoctrine()->getRepository(User::class)->findMyUserByEmail($string, $this->getUser());
        $group = $this->getDoctrine()->getRepository(AddressGroup::class)->findMyAddressBookGroupsByName($string, $this->getUser());

        $res = array('user' => array(), 'group' => array());
        foreach ($user as $data) {
            $res['user'][] = array(
                'name' => $data->getFormatedName($this->parameterBag->get('laf_showName')),
                'id' => $data->getUsername()
            );
        }
        foreach ($group as $data) {
            $tmp = array('name' => '', 'user' => '');
            $tmpUser = array();
            $tmp['name'] = $data->getName();
            foreach ($data->getMember() as $m) {
                $tmpUser[] = $m->getUsername();
            }
            $tmp['user'] = implode($tmpUser, "\n");
            $res['group'][] = $tmp;
        }
        if (sizeof($user) == 0) {
            $res['user'][] = array(
                'name' => $string,
                'id' => $string
            );
        }
        return new JsonResponse($res);
    }

    /**
     * @Route("/room/participant/add", name="room_add_user")
     */
    public function roomAddUser(Request $request, RoomAddService $roomAddService)
    {
        $newMember = array();
        $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(['id' => $request->get('room')]);
        if ($room->getModerator() !== $this->getUser()) {
            return $this->redirectToRoute('dashboard', ['snack' => 'Keine Berechtigung']);
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

            return $this->redirectToRoute('dashboard', ['snack' => $snack]);
        }

        $title = $this->translator->trans('Teilnehmer verwalten');

        return $this->render('room/attendeeModal.twig', array('form' => $form->createView(), 'title' => $title, 'room' => $room));
    }


    /**
     * @Route("/room/participant/remove", name="room_user_remove")
     */
    public
    function roomUserRemove(Request $request, UserService $userService, RoomAddService $roomAddService)
    {

        $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(['id' => $request->get('room')]);
        $repeater = false;

        if ($room->getRepeater()) {
            $repeater = true;
        }
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['id' => $request->get('user')]);
        $snack = 'Keine Berechtigung';
        if ($room->getModerator() === $this->getUser() || $user === $this->getUser()) {
            if (!$repeater) {
                $room->removeUser($user);
                $em = $this->getDoctrine()->getManager();
                $em->persist($room);
                $em->flush();
                $userService->removeRoom($user, $room);
            } else {
                $roomAddService->removeUserFromRoom($user, $room);
            }

            $snack = $this->translator->trans('Teilnehmer gelöscht');
        }

        return $this->redirectToRoute('dashboard', ['snack' => $snack]);
    }

}
