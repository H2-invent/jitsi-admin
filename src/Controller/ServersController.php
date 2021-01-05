<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\User;
use App\Form\Type\NewMemberType;
use App\Form\Type\NewPermissionType;
use App\Form\Type\RoomType;
use App\Form\Type\ServerType;
use App\Service\ServerService;
use App\Service\UserService;
use App\Service\InviteService;
use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ServersController extends AbstractController
{
    /**
     * @Route("/server/add", name="servers_add")
     */
    public function serverAdd(Request $request, ValidatorInterface $validator)
    {
        if ($request->get('id')) {
            $server = $this->getDoctrine()->getRepository(Server::class)->findOneBy(array('id'=>$request->get('id')));
            if ($server->getAdministrator() !== $this->getUser()) {
                return $this->redirectToRoute('dashboard',['snack'=>'Keine Berechtigung']);
            }
            $title = 'Jitsi Meet Server bearbeiten';
        }else {
            $title = 'Jitsi Meet Server erstellen';
            $server = new Server();
            $server->addUser($this->getUser());
            $server->setAdministrator($this->getUser());
        }

        $form = $this->createForm(ServerType::class, $server, ['action' => $this->generateUrl('servers_add',['id'=>$server->getId()])]);
        $form->handleRequest($request);

        $errors = array();
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $errors = $validator->validate($data);
            if (count($errors) == 0) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($data);
                $em->flush();
                return $this->redirectToRoute('dashboard');
            }
        }

        return $this->render('base/__modalView.html.twig', array('form' => $form->createView(), 'title' => $title));

    }

    /**
     * @Route("/server/add-user", name="server_add_user")
     */
    public function roomAddUser(Request $request, InviteService $inviteService, ServerService $serverService)
    {
        $newMember = array();
        $server = $this->getDoctrine()->getRepository(Server::class)->findOneBy(['id' => $request->get('id')]);
        if ($server->getAdministrator() !== $this->getUser()) {
            return $this->redirectToRoute('dashboard',['snack'=>'Keine Berechtigung']);
        }
        $form = $this->createForm(NewPermissionType::class, $newMember, ['action' => $this->generateUrl('server_add_user', ['id' => $server->getId()])]);
        $form->handleRequest($request);

        $errors = array();
        if ($form->isSubmitted() && $form->isValid()) {


            $newMembers = $form->getData();
            $lines = explode("\n", $newMembers['member']);

            if (!empty($lines)) {
                $em = $this->getDoctrine()->getManager();
                foreach ($lines as $line) {
                    $newMember = trim($line);
                    $user = $inviteService->newUser($newMember);
                    $user->addServer($server);
                    $em->persist($user);
                    $serverService->addPermission($server, $user);
                }
                $em->flush();
                $snack = 'Berechtigung hinzugefügt';
                return $this->redirectToRoute('dashboard',['snack'=>$snack]);
            }
        }
        $title = 'Organisator zu Server hinzufügen';

        return $this->render('servers/permissionModal.html.twig', array('form' => $form->createView(), 'title' => $title, 'users'=>$server->getUser(),'server'=>$server));
    }

    /**
     * @Route("/server/user/remove", name="server_user_remove")
     */
    public
    function serverUserRemove(Request $request)
    {

        $server = $this->getDoctrine()->getRepository(Server::class)->findOneBy(['id' => $request->get('id')]);
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['id' => $request->get('user')]);
        $snack = 'Keine Berechtigung';
        if ($server->getAdministrator() === $this->getUser() || $user === $this->getUser()) {
            $server->removeUser($user);
            $em = $this->getDoctrine()->getManager();
            $em->persist($server);
            $em->flush();
            $snack = 'Berechtigung gelöscht';
        }

        return $this->redirectToRoute('dashboard',['snack'=>$snack]);
    }

}
