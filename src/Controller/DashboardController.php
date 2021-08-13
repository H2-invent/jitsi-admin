<?php
/**
 * Created by PhpStorm.
 * User: andreas.holzmann
 * Date: 15.05.2020
 * Time: 09:15
 */

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\User;
use App\Form\Type\JoinViewType;
use App\Service\ServerUserManagment;
use Firebase\JWT\JWT;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class DashboardController
 * @package App\Controller
 */
class DashboardController extends AbstractController
{

    /**
     * @Route("/", name="index")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function index(Request $request)
    {
        if ($this->getUser() || $this->getParameter('laF_startpage') === 'false'){
            return $this->redirectToRoute('dashboard');
        };

        $data = array();
        // dataStr wird mit den Daten uid und email encoded übertragen. Diese werden daraufhin als Vorgaben in das Formular eingebaut
        $dataStr = $request->get('data');
        $dataAll = base64_decode($dataStr);
        parse_str($dataAll, $data);

        $form = $this->createForm(JoinViewType::class, $data,['action'=>$this->generateUrl('join_index')]);
        $form->handleRequest($request);

        $user = $this->getDoctrine()->getRepository(User::class)->findAll();
        $server = $this->getDoctrine()->getRepository(Server::class)->findAll();
        $rooms = $this->getDoctrine()->getRepository(Rooms::class)->findAll();

        return $this->render('dashboard/start.html.twig', ['form' => $form->createView(),'user'=>$user, 'server'=>$server, 'rooms'=>$rooms]);
    }


    /**
     * @Route("/room/dashboard", name="dashboard")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function dashboard(Request $request, ServerUserManagment $serverUserManagment,ParameterBagInterface $parameterBag)
    {
        if ($request->get('join_room') && $request->get('type')) {
            return $this->redirectToRoute('room_join', ['room' => $request->get('join_room'), 't' => $request->get('type')]);
        }

        $roomsFuture = $this->getDoctrine()->getRepository(Rooms::class)->findRoomsInFuture($this->getUser());
        $r = array();
        $future = array();
        foreach ($roomsFuture as $data) {
            $future[$data->getStart()->format('Ymd')][] = $data;
        }
        $em = $this->getDoctrine()->getManager();
        if(!$this->getUser()->getUid()){
            $user = $this->getUser();
            $user->setUid(md5(uniqid()));

            $em->persist($user);
            $em->flush();
        }
        if(!$this->getUser()->getOwnRoomUid()){
            $user = $this->getUser();
            $user->setOwnRoomUid(md5(uniqid()));
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();
        }
        $roomsPast = $this->getDoctrine()->getRepository(Rooms::class)->findRoomsInPast($this->getUser());
        $roomsNow = $this->getDoctrine()->getRepository(Rooms::class)->findRuningRooms($this->getUser());
        $roomsToday = $this->getDoctrine()->getRepository(Rooms::class)->findTodayRooms($this->getUser());
        $persistantRooms = $this->getDoctrine()->getRepository(Rooms::class)->getMyPersistantRooms($this->getUser());
        $servers = $serverUserManagment->getServersFromUser($this->getUser());
        $res = $this->render('dashboard/index.html.twig', [
            'roomsFuture' => $future,
            'roomsPast' => $roomsPast,
            'runningRooms'=>$roomsNow,
            'persistantRooms'=>$persistantRooms,
            'todayRooms' => $roomsToday,
            'snack' => $request->get('snack'),
            'servers'=>$servers,
        ]);
        if (!$this->getUser()->getTimezone()){
            $user = $this->getUser();
            $user->setTimezone(date_default_timezone_get());
            $em->persist($user);
            $em->flush();
        }
        if ($parameterBag->get('laf_darkmodeAsDefault') && !$request->cookies->has('DARK_MODE')){
            $res = $this->redirectToRoute('dashboard');
            $res->headers->setCookie(Cookie::create(
                'DARK_MODE',
                1,
                time() + ( 2 * 365 * 24 * 60 * 60),
                '/',      // Path.
                null,     // Domain.
                false,    // Xmit secure https.
                false     // HttpOnly Flag
            ));
        }
        return $res ;
    }

}
