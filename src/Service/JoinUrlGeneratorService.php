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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;


class JoinUrlGeneratorService
{

    private $parameterBag;

    private $url;


    public function __construct( ParameterBagInterface $parameterBag,UrlGeneratorInterface $urlGenerator)
    {
        $this->parameterBag = $parameterBag;
        $this->url = $urlGenerator;
    }

    function generateUrl(Rooms $room, User $user)
    {

        $data = base64_encode('uid=' . $room->getUid() . '&email=' . $user->getEmail());
        $url = $this->parameterBag->get('laF_baseUrl') . $this->url->generate('join_index', ['data' => $data, 'slug' => $room->getServer()->getSlug()]);
        return $url;
    }


}
