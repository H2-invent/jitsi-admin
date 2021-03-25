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
    public function dashboard(Request $request, ServerUserManagment $serverUserManagment)
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
        $roomsPast = $this->getDoctrine()->getRepository(Rooms::class)->findRoomsInPast($this->getUser());
        $roomsNow = $this->getDoctrine()->getRepository(Rooms::class)->findRuningRooms($this->getUser());
        $roomsToday = $this->getDoctrine()->getRepository(Rooms::class)->findTodayRooms($this->getUser());

        $servers = $serverUserManagment->getServersFromUser($this->getUser());
        $default = $this->getDoctrine()->getRepository(Server::class)->find($this->getParameter('default_jitsi_server_id'));

        if ($default && !in_array($default,$servers)) {
            $servers[] = $default;
        }

        return $this->render('dashboard/index.html.twig', [
            'roomsFuture' => $future,
            'roomsPast' => $roomsPast,
            'runningRooms'=>$roomsNow,
            'todayRooms' => $roomsToday,
            'snack' => $request->get('snack'),
            'servers'=>$servers,
        ]);
    }

}
