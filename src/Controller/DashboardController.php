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
use Firebase\JWT\JWT;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class DashboardController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index(Request $request, TranslatorInterface $translator)
    {
        if ($this->getUser()){
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
     */
    public function dashboard(Request $request)
    {
        $rooms = $this->getUser()->getRooms();
        return $this->render('dashboard/index.html.twig', [
            'rooms' => $rooms,
            'snack' => $request->get('snack')
        ]);
    }

}
