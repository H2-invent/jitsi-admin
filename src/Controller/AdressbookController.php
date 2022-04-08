<?php

namespace App\Controller;

use App\Entity\User;
use App\Helper\JitsiAdminController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdressbookController extends JitsiAdminController
{

    /**
     * @Route("/room/adressbook/remove", name="adressbook_remove_user")
     */
    public function index(Request $request): Response
    {
        $user = $this->doctrine->getRepository(User::class)->find($request->get('id'));
        $myUser = $this->getUser();
        $myUser->removeAddressbook($user);
        $em = $this->doctrine->getManager();
        $em->persist($myUser);
        $em->flush();
     return  $this->redirectToRoute('dashboard');
    }
}
