<?php

namespace App\Service;

use App\Entity\CallerId;
use App\Entity\CallerSession;
use App\Entity\CalloutSession;
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
        private EntityManagerInterface  $em,
        private UserService             $userService,
        private TranslatorInterface     $translator,
        private PermissionChangeService $permissionChangeService
    )
    {
    }


    /**
     * This functions creates participants from a string with new lines.
     * The Function splits the String on newline and then sends each line into the create participant function
     * @param $input
     * @param Rooms $room
     * @return array
     */
    public function createParticipants($input, Rooms $room, ?User $inviter = null)
    {
        $lines = explode("\n", $input);
        $falseEmail = [];
        if (!empty($lines)) {
            foreach ($lines as $line) {
                $user = $this->createUserFromUserUid($line, $falseEmail);
                if ($user) {
                    if (($inviter === $room->getModerator()) || $user !== $room->getCreator()) {
                        $this->createUserParticipant($room, $user);
                    } else {
                        $falseEmail[] = $line;
                    }
                }
            }
        }
        if ($room->getRepeater()) {
            $this->repeaterService->addUserRepeat($room->getRepeater());
        }
        return $falseEmail;
    }


    /**
     * Creates a moderator participant from a string.
     * The participant is first created a a normal participant and then upgraded to a moderator
     * @param $input
     * @param Rooms $room
     * @return array
     */
    public function createModerators($input, Rooms $room, ?User $inviter = null)
    {
        $lines = explode("\n", $input);
        $falseEmail = [];
        if (!empty($lines)) {
            foreach ($lines as $line) {
                $user = $this->createUserFromUserUid($line, $falseEmail);
                if ($user) {
                    if (($inviter === $room->getModerator()) || $user !== $room->getCreator()) {
                        $this->createUserParticipant($room, $user);
                        $this->permissionChangeService->toggleModerator($room->getModerator(), $user, $room);
                    } else {
                        $falseEmail[] = $line;
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


    /**
     * This function creates a user from a given uid.
     * The given uid is mostly a email. can be a username.
     * If allowed a user is created when not in the database. this can be disabled.
     * @param $email
     * @param $falseEmails
     * @return User|null
     */
    public function createUserFromUserUid($email, &$falseEmails): ?User
    {
        $user = null;
        $email = trim($email);
        if ($email !== '') {
            $newMember = $email;
            $user = $this->em->getRepository(User::class)->findOneBy(['email' => $newMember]);
            if (!$user) {
                $user = $this->em->getRepository(User::class)->findOneBy(['username' => $newMember]);
            }
            if ((filter_var($newMember, FILTER_VALIDATE_EMAIL) && $this->parameterBag->get('strict_allow_user_creation') == 1) || $user) {
                if (!$user) {
                    $user = $this->userCreatorService->createUser($email, $email, '', '');
                }
            } else {
                if (strlen($newMember) > 0) {
                    $falseEmails[] = $newMember;
                }
            }
        }
        return $user;
    }


    /**
     * This function generates a participant from a room and user.
     * Is adds the user to the room if it is a non series and adds the user to the series, if the room is a series
     * @param Rooms $room
     * @param User|null $user
     * @return User|null The user which is connected to the room
     */
    private function createUserParticipant(Rooms $room, User $user)
    {
        if ($room->getRepeater()) {
            $this->addUSerToSeries($user, $room);
        } else {
            $this->addUserOnlytoOneRoom($user, $room);
        }
        $this->addUserToAdressbook($room->getModerator(), $user);
        return $user;
    }

    /**
     * This adds a user to a room and sends the email to all participants
     * @param User $user
     * @param Rooms $room
     * @return void
     */
    public function addUserOnlytoOneRoom(User $user, Rooms $room)
    {
        if (!in_array($user, $room->getUser()->toArray())) {
            $user->addRoom($room);
            $this->userService->addUser($user, $room);
            $this->removeRoomUser($user, $room);
        }
    }

    /**
     * @param User $user
     * @param Rooms $room
     * @return User
     */
    public function addUSerToSeries(User $user, Rooms $room)
    {
        $prototype = $room->getRepeater()->getPrototyp();
        if (!in_array($user, $prototype->getPrototypeUsers()->toArray())) {
            $user->addProtoypeRoom($prototype);
            $this->removeRoomUser($user, $prototype);
        }
        return $user;
    }

    /**
     * Adds the user in the addressbook of the inviter/roommoderator
     * @param User $inviter
     * @param User $invited
     * @return void
     */
    public function addUserToAdressbook(User $inviter, User $invited)
    {
        $invited->addAddressbookInverse($inviter);
        $this->em->persist($invited);
        $this->em->flush();
    }

    /**
     * Removes a user from a room. The function checks if the room is a series or a non series.
     * If the room is a series, the participant is removed from all rooms in the series
     * @param User $user
     * @param Rooms $rooms
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function removeUserFromRoom(User $user, Rooms $rooms): string
    {
        if ($rooms->getRepeater()) {
            $this->removeUserFromRepeaterRoom($rooms, $user);
        } else {
            $this->removeUserFromRoomNoRepeat($rooms, $user);
        }
        return $this->translator->trans('Teilnehmer gelöscht');
    }

    /**
     * This function removes the participant from a series
     * @param Rooms $rooms
     * @param User $user
     * @return void
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function removeUserFromRepeaterRoom(Rooms $rooms, User $user)
    {
        $prot = $rooms->getRepeater()->getPrototyp();
        $prot->removePrototypeUser($user);
        $this->em->persist($prot);
        $this->repeaterService->addUserRepeat($rooms->getRepeater());
        $this->repeaterService->sendEMail(
            $rooms->getRepeater(),
            'email/repeaterRemoveUser.html.twig',
            $this->translator->trans(
                'Die Serienvideokonferenz {name} wurde gelöscht',
                ['{name}' => $rooms->getRepeater()->getPrototyp()->getName()]
            ),
            ['room' => $rooms->getRepeater()->getPrototyp()],
            'CANCEL',
            [$user]
        );
    }

    /**
     * Removes the participant from a room. the participant is only removed from one room. even if the room is from a series.
     * @param Rooms $rooms
     * @param User $user
     * @return void
     */
    public function removeUserFromRoomNoRepeat(Rooms $rooms, User $user)
    {
        $rooms->removeUser($user);
        $this->em->persist($rooms);
        $this->em->flush();
        $callerId = $this->em->getRepository(CallerId::class)->findOneBy(['user' => $user, 'room' => $rooms]);
        if ($callerId) {
            $this->em->remove($callerId);
            $this->em->flush();
        }
        $userRoom = $this->em->getRepository(RoomsUser::class)->findOneBy(['room' => $rooms, 'user' => $user]);
        if ($userRoom) {
            $this->em->remove($userRoom);
            $this->em->flush();
        }
        $calloutSession = $this->em->getRepository(CalloutSession::class)->findOneBy(['room' => $rooms, 'user' => $user]);
        if ($calloutSession) {
            $this->em->remove($calloutSession);
            $this->em->flush();
        }
        $this->userService->removeRoom($user, $rooms);
    }

    /**
     * Removes the permission entity.
     * @param User $user
     * @param Rooms $rooms
     * @return void
     */
    private function removeRoomUser(User $user, Rooms $rooms)
    {
        $roomsUser = $this->em->getRepository(RoomsUser::class)->findOneBy(['user' => $user, 'room' => $rooms]);
        if ($roomsUser) {
            $this->em->remove($roomsUser);
        }
        $this->em->flush();
    }
}
