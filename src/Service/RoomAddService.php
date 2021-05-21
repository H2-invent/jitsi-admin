<?php


namespace App\Service;


use App\Entity\Rooms;
use App\Entity\RoomsUser;
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
                    $user = $this->createUserParticipant($newMember,$room);
                } else {
                    if (strlen($newMember)>0) {
                        $falseEmail[] = $newMember;
                    }

                }
            }
            $this->em->flush();
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
                    $user = $this->createUserParticipant($newMember,$room);
                    $roomsUser = new RoomsUser();
                    $roomsUser->setUser($user);
                    $roomsUser->setRoom($room);
                    $roomsUser->setModerator(true);
                    $this->em->persist($roomsUser);
                } else {
                    if (strlen($newMember)>0) {
                        $falseEmail[] = $newMember;
                    }

                }
            }
            $this->em->flush();

        }

        return $falseEmail;

    }
    private function createUserParticipant($email,Rooms $room){
        $user = $this->inviteService->newUser($email);
        $user->addRoom($room);
        $user->addAddressbookInverse($room->getModerator());
        $this->em->persist($user);
        $this->userService->addUser($user, $room);
        $roomsUser = $this->em->getRepository(RoomsUser::class)->findOneBy(array('user' => $user, 'room' => $room));
        if ($roomsUser) {
            $this->em->remove($roomsUser);
        }
        $this->em->flush();
        return $user;
    }
}