<?php


namespace App\Service;


use App\Entity\Rooms;
use App\Entity\RoomsUser;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class PermissionChangeService
{
    private $em;
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    function toggleShareScreen(User $oldUser,User $user, Rooms $rooms){
        if($rooms->getModerator() === $oldUser){
            $roomsUser = $this->em->getRepository(RoomsUser::class)->findOneBy(array('user'=>$user,'room'=>$rooms));
            if(!$roomsUser){
                $roomsUser = new RoomsUser();
                $roomsUser->setUser($user);
                $roomsUser->setRoom($rooms);
            }
            if($roomsUser->getShareDisplay()){
                $roomsUser->setShareDisplay(false);
            }else{
                $roomsUser->setShareDisplay(true);
            }
            $this->em->persist($roomsUser);
            $this->em->flush();
            return true;
        }
        return false;
    }

    function toggleModerator(User $oldUser,User $user, Rooms $rooms){
        if($rooms->getModerator() === $oldUser){
            $roomsUser = $this->em->getRepository(RoomsUser::class)->findOneBy(array('user'=>$user,'room'=>$rooms));
            if(!$roomsUser){
                $roomsUser = new RoomsUser();
                $roomsUser->setUser($user);
                $roomsUser->setRoom($rooms);
            }
            if($roomsUser->getModerator()){
                $roomsUser->setModerator(false);
            }else{
                $roomsUser->setModerator(true);
            }
            $this->em->persist($roomsUser);
            $this->em->flush();
            return true;
        }
        return false;
    }
    function togglePrivateMessage(User $oldUser,User $user, Rooms $rooms){
        if($rooms->getModerator() === $oldUser){
            $roomsUser = $this->em->getRepository(RoomsUser::class)->findOneBy(array('user'=>$user,'room'=>$rooms));
            if(!$roomsUser){
                $roomsUser = new RoomsUser();
                $roomsUser->setUser($user);
                $roomsUser->setRoom($rooms);
            }
            if($roomsUser->getPrivateMessage()){
                $roomsUser->setPrivateMessage(false);
            }else{
                $roomsUser->setPrivateMessage(true);
            }
            $this->em->persist($roomsUser);
            $this->em->flush();
            return true;
        }
        return false;
    }
}