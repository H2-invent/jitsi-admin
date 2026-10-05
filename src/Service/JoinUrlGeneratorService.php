<?php

/**
 * Created by PhpStorm.
 * User: Emanuel
 * Date: 03.10.2019
 * Time: 19:01
 */

namespace App\Service;

use App\Entity\Rooms;
use App\Entity\User;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class JoinUrlGeneratorService
{
    public function __construct(private readonly CreateHttpsUrl $createHttps, private readonly UrlGeneratorInterface $url)
    {
    }

    public function generateUrl(Rooms $room, User $user): string
    {

        $data = base64_encode('uid=' . $room->getUid() . '&email=' . $user->getEmail());
        $url = $this->createHttps->createHttpsUrl(
            $room->getPersistantRoom() ?
                $this->url->generate('join_index_uid', ['data' => $data, 'uid' => $room->getUid(), 'slug' => $room->getServer()->getSlug()]) :
                $this->url->generate('join_index', ['data' => $data, 'slug' => $room->getServer()->getSlug()]),
            $room
        );
        return $url;
    }
}
