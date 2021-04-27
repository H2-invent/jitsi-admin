<?php

namespace App\Controller;

use App\Entity\KeycloakGroupsToServers;
use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\User;
use App\Form\Type\EnterpriseType;
use App\Form\Type\NewMemberType;
use App\Form\Type\NewPermissionType;
use App\Form\Type\RoomType;
use App\Form\Type\ServerType;
use App\Service\LicenseService;
use App\Service\MailerService;
use App\Service\ServerService;
use App\Service\ServerUserManagment;
use App\Service\UserService;
use App\Service\InviteService;
use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ServersController extends AbstractController
{
    /**
     * @Route("/server/add", name="servers_add")
     */
    public function serverAdd(Request $request, ValidatorInterface $validator, ServerService $serverService, TranslatorInterface $translator)
    {
        if ($request->get('id')) {
            $server = $this->getDoctrine()->getRepository(Server::class)->findOneBy(array('id' => $request->get('id')));
            if ($server->getAdministrator() !== $this->getUser()) {
                return $this->redirectToRoute('dashboard', ['snack' => 'Keine Berechtigung']);
            }
            $title = $translator->trans('Jitsi-Meet-Server bearbeiten');
        } else {
            $title = $translator->trans('Jitsi-Meet-Server erstellen');
            $server = new Server();
            $server->addUser($this->getUser());
            $server->setAdministrator($this->getUser());
        }

        $form = $this->createForm(ServerType::class, $server, ['action' => $this->generateUrl('servers_add', ['id' => $server->getId()])]);
        $form->handleRequest($request);

        $errors = array();
        if ($form->isSubmitted() && $form->isValid()) {
            $server = $form->getData();
            $url = $server->getUrl();
            $url = str_replace('https://', '', $url);
            $url = str_replace('http://', '', $url);
            $server->setUrl($url);
            $errors = $validator->validate($server);
            if (count($errors) == 0) {
                $em = $this->getDoctrine()->getManager();
                if (!$server->getSlug()) {
                    $slug = $serverService->makeSlug($server->getUrl());
                    $server->setSlug($slug);
                }
                $em->persist($server);
                $em->flush();
                return $this->redirectToRoute('dashboard');
            }
        }

        return $this->render('servers/__addServerModal.html.twig', array('form' => $form->createView(), 'title' => $title, 'server' => $server));

    }

    /**
     * @Route("/server/enterprise", name="servers_enterprise")
     */
    public function serverEnterprise(Request $request, ValidatorInterface $validator, ServerService $serverService, TranslatorInterface $translator, LicenseService $licenseService)
    {

        $server = $this->getDoctrine()->getRepository(Server::class)->findOneBy(array('id' => $request->get('id')));
        if ($server->getAdministrator() !== $this->getUser() || !$licenseService->verify($server)) {
            return $this->redirectToRoute('dashboard', ['snack' => 'Keine Berechtigung']);
        }
        $title = $translator->trans('Jitsi-Admin Enterprise Einstellungen');


        $form = $this->createForm(EnterpriseType::class, $server, ['action' => $this->generateUrl('servers_enterprise', ['id' => $server->getId()])]);
        $form->handleRequest($request);

        $errors = array();
        if ($form->isSubmitted() && $form->isValid()) {
            $server = $form->getData();
            $errors = $validator->validate($server);
            if (count($errors) == 0) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($server);
                $em->flush();
                return $this->redirectToRoute('dashboard');
            }
        }

        return $this->render('servers/__serverEnterpriseModal.html.twig', array('form' => $form->createView(), 'title' => $title, 'server' => $server));

    }

    /**
     * @Route("/server/add-user", name="server_add_user")
     */
    public function roomAddUser(Request $request, InviteService $inviteService, ServerService $serverService, TranslatorInterface $translator)
    {
        $newMember = array();
        $server = $this->getDoctrine()->getRepository(Server::class)->findOneBy(['id' => $request->get('id')]);
        if ($server->getAdministrator() !== $this->getUser()) {
            return $this->redirectToRoute('dashboard', ['snack' => 'Keine Berechtigung']);
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
                return $this->redirectToRoute('dashboard', ['snack' => $snack]);
            }
        }
        $title = $translator->trans('Organisator zu Server hinzufügen');

        return $this->render('servers/permissionModal.html.twig', array('form' => $form->createView(), 'title' => $title, 'users' => $server->getUser(), 'server' => $server));
    }

    /**
     * @Route("/server/user/remove", name="server_user_remove")
     */
    public
    function serverUserRemove(Request $request, TranslatorInterface $translator)
    {

        $server = $this->getDoctrine()->getRepository(Server::class)->findOneBy(['id' => $request->get('id')]);
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['id' => $request->get('user')]);
        $snack = $translator->trans('Keine Berechtigung');
        if ($server->getAdministrator() === $this->getUser() || $user === $this->getUser()) {
            $server->removeUser($user);
            $em = $this->getDoctrine()->getManager();
            $em->persist($server);
            $em->flush();
            $snack = $translator->trans('Berechtigung gelöscht');
        }

        return $this->redirectToRoute('dashboard', ['snack' => $snack]);
    }

    /**
     * @Route("/server/delete", name="server_delete")
     */
    public
    function serverDelete(Request $request, TranslatorInterface $translator, ServerService $serverService)
    {

        $server = $this->getDoctrine()->getRepository(Server::class)->findOneBy(['id' => $request->get('id')]);
        $snack = $translator->trans('Keine Berechtigung');
        if ($server->getAdministrator() === $this->getUser()) {
            $em = $this->getDoctrine()->getManager();
            $groupServer = $this->getDoctrine()->getRepository(KeycloakGroupsToServers::class)->findBy(array('server' => $server));
            foreach ($groupServer as $data) {
                $em->remove($data);
            }
            foreach ($server->getUser() as $user) {
                $server->removeUser($user);
                $em->persist($server);
            }
            $em->flush();

            $snack = $translator->trans('Server gelöscht');
        }

        return $this->redirectToRoute('dashboard', ['snack' => $snack]);
    }

    /**
     * @Route("/server/check/email", name="server_check_email")
     */
    public
    function servercheckEmail(Request $request, TranslatorInterface $translator, MailerService $mailerService)
    {
        $res = ['snack' => $translator->trans('SMTP Einstellungen korrekt. Sie sollten in Kürze eine Email erhalten'), 'color' => 'success'];
        $server = $this->getDoctrine()->getRepository(Server::class)->find($request->get('id'));
        if (!$server || $server->getAdministrator() != $this->getUser()) {

            $res = ['snack' => $translator->trans('Fehler, der Server ist nicht registriert'), 'color' => 'danger'];
        } else {
            try {
                $r = $mailerService->sendEmail(
                    $this->getUser()->getEmail(),
                    $translator->trans('Testmail vom Jitsi-Admin') . ' | ' . $server->getUrl(),
                    '<h1>' . $translator->trans('Sie haben einen SMTP-Server für Ihren Jitsi-Server erfolgreich eingerichtet') . '</h1>'
                    . $server->getSmtpHost() . '<br>'
                    . $server->getSmtpEmail() . '<br>'
                    . $server->getSmtpSenderName() . '<br>',
                    $server
                );
                if (!$r) {
                    $res = ['snack' => $translator->trans('Fehler, Ihre SMTP-Parameter sind fehlerhaft'), 'color' => 'danger'];
                }
            } catch (\Exception $e) {
                $res = ['snack' => $translator->trans('Fehler, Ihre SMTP-Parameter sind fehlerhaft'), 'color' => 'danger'];
            }
        }

        return $this->redirectToRoute('dashboard', $res);

    }
}
