<?php

namespace App\Service;

use App\Entity\Rooms;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FavoriteService
{
    private $em;
    private $client;
    public function __construct(HttpClientInterface $client, EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
        $this->client = $client;
    }

    public function changeFavorite(User $user, Rooms $room)
    {
        if (in_array($user, $room->getUser()->toArray())) {
            if (in_array($room, $user->getFavorites()->toArray())) {
                $user->removeFavorite($room);
            } else {
                $user->addFavorite($room);
            }
            $this->em->persist($user);
            $this->em->flush();
        }else{
            return false;
        }
        return true;
    }
    public function cleanFavorites(User $user){
        $favs = $user->getFavorites();
        $now = (new \DateTime())->setTimezone(new \DateTimeZone('utc'));
        foreach ($favs as $data){
            if($data->getPersistantRoom() !== true && $data->getScheduleMeeting() !== true && $data->getEndDateUtc() < $now ){
                $user->removeFavorite($data);
            }
        }
        $this->em->persist($user);
        $this->em->flush();
    }
    public function sendMe(){
        try {
            $response = $this->client->request('GET', 'https://...', [
                // 0 means to not follow any redirect
                'max_redirects' => 0,
            ]);
        }catch (\Exception $exception){
        }
    }
}