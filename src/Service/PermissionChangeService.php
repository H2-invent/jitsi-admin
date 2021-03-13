<?php


namespace App\Service;


use App\Entity\Rooms;
use App\Entity\RoomsUser;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class PermissionChangeService
 * @package App\Service
 */
class PermissionChangeService
{
    private $em;
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     *  When this function is called then a user is allowed to share the screen or is not alloed to share the screen
     * The Function toggle this attribute
     * @param User $oldUser
     * @param User $user
     * @param Rooms $rooms
     * @return bool
     */
    function toggleShareScreen(User $oldUser, User $user, Rooms $rooms){
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

    /**
     *   When this function is called then a user is set as an moderator
     * The Function toggle this attribute
     * @param User $oldUser
     * @param User $user
     * @param Rooms $rooms
     * @return bool
     */
    function toggleModerator(User $oldUser, User $user, Rooms $rooms){
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

    /**
     * When this function is called then a user is allowed to send private mesages or is not alloed to send private messages.
     * The Function toggle this attribute
     * @param User $oldUser
     * @param User $user
     * @param Rooms $rooms
     * @return bool
     */
    function togglePrivateMessage(User $oldUser, User $user, Rooms $rooms){
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