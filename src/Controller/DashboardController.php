<?php
/**
 * Created by PhpStorm.
 * User: andreas.holzmann
 * Date: 15.05.2020
 * Time: 09:15
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index(Request $request)
    {
        return $this->render('dashboard/start.html.twig', []);
    }


    /**
     * @Route("/room", name="dashboard")
     */
    public function dashboard(Request $request)
    {
        $rooms = $this->getUser()->getRooms();
        return $this->render('dashboard/index.html.twig', [
            'rooms' => $rooms
        ]);
    }

}
