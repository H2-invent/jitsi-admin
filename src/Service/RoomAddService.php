<?php


namespace App\Service;


use App\Entity\CallerId;
use App\Entity\Repeat;
use App\Entity\Rooms;
use App\Entity\RoomsUser;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RoomAddService
{

    public function __construct(
        private UserCreatorService      $userCreatorService,
        private ParameterBagInterface   $parameterBag,
        private RepeaterService         $repeaterService,
        private InviteService           $inviteService,
        private EntityManagerInterface  $em,
        private UserService             $userService,
        private TranslatorInterface     $translator,
        private PermissionChangeService $permissionChangeService
    )
    {
    }


    public
    function createParticipants($input, Rooms $room)
    {
        $lines = explode("\n", $input);
        $falseEmail = array();
        if (!empty($lines)) {
            foreach ($lines as $line) {
                if (trim($line) !== '') {
                    $newMember = trim($line);
                    $tmpUser = null;
                    $tmpUser = $this->em->getRepository(User::class)->findOneBy(array('email' => $newMember));
                    if (!$tmpUser) {
                        $tmpUser = $this->em->getRepository(User::class)->findOneBy(array('username' => $newMember));
                    }
                    if ((filter_var($newMember, FILTER_VALIDATE_EMAIL) && $this->parameterBag->get('strict_allow_user_creation') == 1) || $tmpUser) {
                        $this->createUserParticipant($newMember, $room, $tmpUser);
                    } else {
                        if (strlen($newMember) > 0) {
                            $falseEmail[] = $newMember;
                        }
                    }
                }
            }
        }
        if ($room->getRepeater()) {
            $this->repeaterService->addUserRepeat($room->getRepeater());
        }
        return $falseEmail;
    }

    public
    function createModerators($input, Rooms $room)
    {
        $lines = explode("\n", $input);
        $falseEmail = array();
        if (!empty($lines)) {
            foreach ($lines as $line) {
                if (trim($line) !== '') {
                    $newMember = trim($line);
                    $tmpUser = null;
                    $tmpUser = $this->em->getRepository(User::class)->findOneBy(array('email' => $newMember));
                    if (!$tmpUser) {
                        $tmpUser = $this->em->getRepository(User::class)->findOneBy(array('username' => $newMember));
                    }
                    if ((filter_var($newMember, FILTER_VALIDATE_EMAIL) && $this->parameterBag->get('strict_allow_user_creation') == 1) || $tmpUser) {
                        $user = $this->createUserParticipant($newMember, $room, $tmpUser);
                        $this->permissionChangeService->toggleModerator($room->getModerator(), $user, $room);
                    } else {
                        if (strlen($newMember) > 0) {
                            $falseEmail[] = $newMember;
                        }

                    }
                }

            }
            $this->em->flush();
        }
        if ($room->getRepeater()) {
            $this->repeaterService->addUserRepeat($room->getRepeater());
        }

        return $falseEmail;
    }

    private
    function createUserParticipant($email, Rooms $room, ?User $user = null)
    {
        if (!$user) {
            $user = $this->userCreatorService->createUser($email, $email, '', '');
        }

        if ($room->getRepeater()) {
            $prototype = $room->getRepeater()->getPrototyp();
            if (!in_array($user, $prototype->getPrototypeUsers()->toArray())) {
                $user->addProtoypeRoom($prototype);
                $this->removeRoomUser($user, $prototype);
            }

        } else {
            if (!in_array($user, $room->getUser()->toArray())) {
                $user->addRoom($room);
                $this->userService->addUser($user, $room);
                $this->removeRoomUser($user, $room);
            }
        }
        $user->addAddressbookInverse($room->getModerator());
        $this->em->persist($user);
        $this->em->flush();
        return $user;
    }

    public
    function removeUserFromRoom(User $user, Rooms $rooms): string
    {
        if ($rooms->getRepeater()) {
            $prot = $rooms->getRepeater()->getPrototyp();
            $prot->removePrototypeUser($user);
            $this->em->persist($prot);
            $this->repeaterService->addUserRepeat($rooms->getRepeater());
            $this->repeaterService->sendEMail(
                $rooms->getRepeater(),
                'email/repeaterRemoveUser.html.twig',
                $this->translator->trans('Die Serienvideokonferenz {name} wurde gelöscht',
                    array('{name}' => $rooms->getRepeater()->getPrototyp()->getName())),
                array('room' => $rooms->getRepeater()->getPrototyp()),
                'CANCEL',
                array($user)
            );
        } else {
            $rooms->removeUser($user);
            $this->em->persist($rooms);
            $this->em->flush();
            $callerId = $this->em->getRepository(CallerId::class)->findOneBy(array('user'=>$user,'room'=>$rooms));
            if ($callerId){
                $this->em->remove($callerId);
                $this->em->flush();
            }
            $userRoom = $this->em->getRepository(RoomsUser::class)->findOneBy(array('room'=>$rooms, 'user'=>$user));
            if ($userRoom){
                $this->em->remove($userRoom);
                $this->em->flush();
            }
            $this->userService->removeRoom($user, $rooms);
        }

        return $this->translator->trans('Teilnehmer gelöscht');
    }


    private
    function removeRoomUser(User $user, Rooms $rooms)
    {
        $roomsUser = $this->em->getRepository(RoomsUser::class)->findOneBy(array('user' => $user, 'room' => $rooms));
        if ($roomsUser) {
            $this->em->remove($roomsUser);
        }
        $this->em->flush();
    }
}
