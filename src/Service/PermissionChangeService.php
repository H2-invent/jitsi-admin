<?php


namespace App\Service;


use App\Entity\LobbyWaitungUser;
use App\Entity\Rooms;
use App\Entity\RoomsUser;
use App\Entity\User;
use App\Service\Lobby\DirectSendService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class PermissionChangeService
 * @package App\Service
 */
class PermissionChangeService
{
    private $em;
    private $roomAddUserService;
    private $repeaterService;
    private $websocketService;
    private $translator;
    private $urlGen;
    private $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag, UrlGeneratorInterface $urlGenerator, RepeaterService $repeaterService, EntityManagerInterface $em, RoomAddService $roomAddService, DirectSendService $directSendService, TranslatorInterface $translator)
    {
        $this->em = $em;
        $this->roomAddUserService = $roomAddService;
        $this->repeaterService = $repeaterService;
        $this->websocketService = $directSendService;
        $this->translator = $translator;
        $this->urlGen = $urlGenerator;
        $this->parameterBag = $parameterBag;
    }

    /**
     *  When this function is called then a user is allowed to share the screen or is not alloed to share the screen
     * The Function toggle this attribute
     * @param User $oldUser
     * @param User $user
     * @param Rooms $rooms
     * @return bool
     */
    function toggleShareScreen(User $oldUser, User $user, Rooms $rooms)
    {
        $repeater = false;
        if ($rooms->getRepeater()) {
            $rooms = $rooms->getRepeater()->getPrototyp();
            $repeater = true;
        }
        if ($rooms->getModerator() === $oldUser) {
            $roomsUser = $this->em->getRepository(RoomsUser::class)->findOneBy(array('user' => $user, 'room' => $rooms));
            if (!$roomsUser) {
                $roomsUser = new RoomsUser();
                $roomsUser->setUser($user);
                $roomsUser->setRoom($rooms);
            }
            if ($roomsUser->getShareDisplay()) {
                $roomsUser->setShareDisplay(false);
            } else {
                $roomsUser->setShareDisplay(true);
            }
            $this->em->persist($roomsUser);
            $this->em->flush();
            if ($repeater) {
                $this->repeaterService->addUserRepeat($rooms->getRepeaterProtoype());
            }
            return true;
        }
        return false;
    }

    /**
     *   When this function is called then a user is set as an moderator
     * The Function toggle this attribute
     * @param User $oldUser
     * @param User $user
     * @param Rooms $rooms
     * @return bool
     */
    function toggleModerator(User $oldUser, User $user, Rooms $rooms)
    {
        $repeater = false;
        if ($rooms->getRepeater()) {
            $rooms = $rooms->getRepeater()->getPrototyp();
            $repeater = true;
        }
        if ($rooms->getModerator() === $oldUser) {
            $roomsUser = $this->em->getRepository(RoomsUser::class)->findOneBy(array('user' => $user, 'room' => $rooms));
            if (!$roomsUser) {
                $roomsUser = new RoomsUser();
                $roomsUser->setUser($user);
                $roomsUser->setRoom($rooms);
            }
            if ($roomsUser->getModerator()) {
                $roomsUser->setModerator(false);
            } else {
                $roomsUser->setModerator(true);
            }
            $this->em->persist($roomsUser);
            $this->em->flush();
            if ($repeater) {
                $this->repeaterService->addUserRepeat($rooms->getRepeaterProtoype());
            }
            return true;
        }

        return false;
    }

    /**
     *   When this function is called then a user is set as an moderator
     * The Function toggle this attribute
     * @param User $oldUser
     * @param User $user
     * @param Rooms $rooms
     * @return bool
     */
    function toggleLobbyModerator(User $oldUser, User $user, Rooms $rooms)
    {
        $repeater = false;
        if ($rooms->getRepeater()) {
            $rooms = $rooms->getRepeater()->getPrototyp();
            $repeater = true;
        }
        if ($rooms->getModerator() === $oldUser) {
            $roomsUser = $this->em->getRepository(RoomsUser::class)->findOneBy(array('user' => $user, 'room' => $rooms));
            if (!$roomsUser) {
                $roomsUser = new RoomsUser();
                $roomsUser->setUser($user);
                $roomsUser->setRoom($rooms);
            }
            if ($roomsUser->getLobbyModerator()) {
                $roomsUser->setLobbyModerator(false);
            } else {
                $roomsUser->setLobbyModerator(true);
            }
            $this->em->persist($roomsUser);
            $this->em->flush();
            if ($repeater) {
                $this->repeaterService->addUserRepeat($rooms->getRepeaterProtoype());
            }
            $lobbyUser = $this->em->getRepository(LobbyWaitungUser::class)->findOneBy(array('user'=>$user,'room'=>$rooms));
            if($lobbyUser){
                $this->em->remove($lobbyUser);
                $this->em->flush();
            }
            $topic = 'lobby_personal' . $rooms->getUidReal()  . $user->getUid();
            $this->websocketService->sendSnackbar($topic, $this->translator->trans('lobby.change.moderator.permissions'), 'info');
            $this->websocketService->sendReloadPage($topic, $this->parameterBag->get('laf_lobby_popUpDuration'));
            $this->websocketService->sendRefresh('lobby_moderator/'.$rooms->getUidReal(),
                $this->urlGen->generate('lobby_moderator', array('uid' => $rooms->getUidReal())) . ' #waitingUser');

            return $roomsUser;
        }

        return false;
    }

    /**
     * When this function is called then a user is allowed to send private mesages or is not alloed to send private messages.
     * The Function toggle this attribute
     * @param User $oldUser
     * @param User $user
     * @param Rooms $rooms
     * @return bool
     */
    function togglePrivateMessage(User $oldUser, User $user, Rooms $rooms)
    {
        $repeater = false;
        if ($rooms->getRepeater()) {
            $rooms = $rooms->getRepeater()->getPrototyp();
            $repeater = true;
        }
        if ($rooms->getModerator() === $oldUser) {
            $roomsUser = $this->em->getRepository(RoomsUser::class)->findOneBy(array('user' => $user, 'room' => $rooms));
            if (!$roomsUser) {
                $roomsUser = new RoomsUser();
                $roomsUser->setUser($user);
                $roomsUser->setRoom($rooms);
            }
            if ($roomsUser->getPrivateMessage()) {
                $roomsUser->setPrivateMessage(false);
            } else {
                $roomsUser->setPrivateMessage(true);
            }
            $this->em->persist($roomsUser);
            $this->em->flush();
            if ($repeater) {
                $this->repeaterService->addUserRepeat($rooms->getRepeaterProtoype());
            }
            return true;
        }
        return false;
    }
}