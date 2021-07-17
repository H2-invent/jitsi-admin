<?php

namespace App\Controller;

use App\Entity\AddressGroup;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use function GuzzleHttp\Psr7\str;

class ParticipantController extends AbstractController
{
    /**
     * @Route("/room/participant", name="search_participant")
     */
    public function index(Request $request, TranslatorInterface $translator): Response
    {
        $string = $request->get('search');
        $user = $this->getDoctrine()->getRepository(User::class)->findMyUserByEmail($string, $this->getUser());
        $group = $this->getDoctrine()->getRepository(AddressGroup::class)->findMyAddressBookGroupsByName($string, $this->getUser());

        $res = array();
        foreach ($user as $data) {
            $res['user'][] = $data->getEmail();
        }
        foreach ($group as $data) {
            $tmp = array('name' => '', 'user' => '');
            $tmpUser = array();
            $tmp['name'] = $data->getName();
            foreach ($data->getMember() as $m) {
                $tmpUser[] = $m->getEmail();
            }
            $tmp['user'] = implode($tmpUser, "\n");
            $res['group'][] = $tmp;
        }
        if (sizeof($user) == 0) {
            $res['user'][] = $string;
        }
        return new JsonResponse($res);
    }
}
