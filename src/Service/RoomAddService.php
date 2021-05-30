<?php


namespace App\Service;


use App\Entity\Rooms;
use App\Entity\RoomsUser;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RoomAddService
{
    private $inviteService;
    private $em;
    private $userService;
    private $translator;

    public function __construct(InviteService $inviteService, EntityManagerInterface $entityManager, UserService $userService, TranslatorInterface $translator)
    {
        $this->inviteService = $inviteService;
        $this->em = $entityManager;
        $this->userService = $userService;
        $this->translator = $translator;
    }


    public function createParticipants($input, Rooms $room)
    {
        $lines = explode("\n", $input);
        $snack = null;
        $falseEmail = array();
        if (!empty($lines)) {
            foreach ($lines as $line) {
                $newMember = trim($line);
                if (filter_var($newMember, FILTER_VALIDATE_EMAIL)) {
                    $user = $this->createUserParticipant($newMember, $room);
                } else {
                    if (strlen($newMember) > 0) {
                        $falseEmail[] = $newMember;
                    }

                }
            }
        }
        if ($room->getRepeater()) {
            $this->addUserRepeat($room->getRepeater()->getPrototyp());
        }
        return $falseEmail;
    }

    public function createModerators($input, Rooms $room)
    {
        $lines = explode("\n", $input);
        $snack = null;
        $falseEmail = array();
        if (!empty($lines)) {
            foreach ($lines as $line) {
                $newMember = trim($line);
                if (filter_var($newMember, FILTER_VALIDATE_EMAIL)) {
                    $user = $this->createUserParticipant($newMember, $room);
                    $roomsUser = new RoomsUser();
                    $roomsUser->setUser($user);
                    $roomsUser->setRoom($room->getRepeater()?$room->getRepeater()->getPrototyp():$room);
                    $roomsUser->setModerator(true);
                    $this->em->persist($roomsUser);
                } else {
                    if (strlen($newMember) > 0) {
                        $falseEmail[] = $newMember;
                    }

                }
            }
            $this->em->flush();
        }
        if ($room->getRepeater()) {
            $this->addUserRepeat($room->getRepeater()->getPrototyp());
        }

        return $falseEmail;

    }

    private function createUserParticipant($email, Rooms $room)
    {
        $user = $this->inviteService->newUser($email);
        if ($room->getRepeater()) {
            $room = $room->getRepeater()->getPrototyp();
            $user->addProtoypeRoom($room);
        } else {
            $user->addRoom($room);
            $this->userService->addUser($user, $room);
        }

        $user->addAddressbookInverse($room->getModerator());
        $this->em->persist($user);
        $roomsUser = $this->em->getRepository(RoomsUser::class)->findOneBy(array('user' => $user, 'room' => $room));
        if ($roomsUser) {
            $this->em->remove($roomsUser);
        }
        $this->em->flush();
        return $user;
    }

    public function removeUserFromRoom(User $user, Rooms $rooms)
    {
        if ($rooms->getRepeater()) {
            $prot = $rooms->getRepeater()->getPrototyp();
            $prot->removePrototypeUser($user);
            $this->em->persist($prot);
            $this->addUserRepeat($prot);
        } else {
            $rooms->removeUser($user);
            $this->em->persist($rooms);
            $this->em->flush();
        }

    }

    public function addUserRepeat(Rooms $prototype)
    {
        foreach ($prototype->getRepeaterProtoype()->getRooms() as $data) {
            foreach ($data->getUser() as $data2) {
                $data->removeUser($data2);
                $this->em->persist($data);
            }
            foreach ($prototype->getPrototypeUsers() as $data2) {
                $data->addUser($data2);
                $this->em->persist($data);
            }
        }
        foreach ($prototype->getRepeaterProtoype()->getRooms() as $data) {
            foreach ($data->getUserAttributes() as $data2) {
                $this->em->remove($data2);
            }
            foreach ($prototype->getUserAttributes() as $data2) {
                $tmp = clone $data2;
                $tmp->setRoom($data);
                $this->em->persist($tmp);
            }
        }
        $this->em->flush();
    }

}