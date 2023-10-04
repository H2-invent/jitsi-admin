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
use App\Helper\JitsiAdminController;
use App\Service\LicenseService;
use App\Service\MailerService;
use App\Service\ServerService;
use App\Service\ServerUserManagment;
use App\Service\UserCreatorService;
use App\Service\UserService;
use App\Service\InviteService;
use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\Common\Collections\ArrayCollection;

class ServersController extends JitsiAdminController
{
    /**
     * @Route("/server/add", name="servers_add")
     */
    public function serverAdd(Request $request, ValidatorInterface $validator, ServerService $serverService, TranslatorInterface $translator)
    {
        $originalKeycloakGroups = new ArrayCollection();

        if ($request->get('id')) {
            $server = $this->doctrine->getRepository(Server::class)->findOneBy(['id' => $request->get('id')]);

            foreach ($server->getkeycloakGroups() as $keycloakGroup) {
                $originalKeycloakGroups->add($keycloakGroup);
            }

            if ($server->getAdministrator() !== $this->getUser()) {
                $this->addFlash('danger', $translator->trans('Keine Berechtigung'));
                return $this->redirectToRoute('dashboard');
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

        $errors = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $server = $form->getData();
            $url = $server->getUrl();
            $url = str_replace('https://', '', $url);
            $url = str_replace('http://', '', $url);
            $server->setUrl($url);
            $errors = $validator->validate($server);
            if (count($errors) == 0) {
                $em = $this->doctrine->getManager();
                if (!$server->getSlug()) {
                    $slug = $serverService->makeSlug($server->getUrl());
                    $server->setSlug($slug);
                }

                foreach ($originalKeycloakGroups as $KeycloakGroup) {
                    if (false === $server->getKeycloakGroups()->contains($KeycloakGroup)) {
                        $em->remove($KeycloakGroup);
                    }
                }

                $em->persist($server);
                $em->flush();
                $this->addFlash('success', $translator->trans('Ihre Eingabe wurde Erfolgreich gespeichert.'));
                return $this->redirectToRoute('dashboard');
            }
        }

        return $this->render('servers/__addServerModal.html.twig', ['form' => $form->createView(), 'title' => $title, 'server' => $server]);
    }

    /**
     * @Route("/server/enterprise", name="servers_enterprise")
     */
    public function serverEnterprise(Request $request, ValidatorInterface $validator, ServerService $serverService, TranslatorInterface $translator, LicenseService $licenseService)
    {

        $server = $this->doctrine->getRepository(Server::class)->findOneBy(['id' => $request->get('id')]);
        if ($server->getAdministrator() !== $this->getUser()) {
            $this->addFlash('danger', $translator->trans('Keine Berechtigung'));
            return $this->redirectToRoute('dashboard');
        }
        $title = $translator->trans('Jitsi-Admin Enterprise Einstellungen');


        $form = $this->createForm(EnterpriseType::class, $server, ['action' => $this->generateUrl('servers_enterprise', ['id' => $server->getId()])]);
        $form->handleRequest($request);

        $errors = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $server = $form->getData();

            $errors = $validator->validate($server);
            if (count($errors) == 0) {
                $em = $this->doctrine->getManager();
                $em->persist($server);
                $em->flush();
                if ($server->getServerBackgroundImage()) {
                    $server->getServerBackgroundImage()->setUpdatedAt(new \DateTime());
                    $server->setUpdatedAt(new \DateTime());
                    $em->persist($server);
                    $em->flush();
                }

                if ($server->getServerBackgroundImage() && !$server->getServerBackgroundImage()->getDocumentFileName()) {
                    $server->setServerBackgroundImage(null);
                    $em->persist($server);
                    $em->flush();
                }

                $this->addFlash('success', $translator->trans('Ihre Eingabe wurde Erfolgreich gespeichert.'));
                return $this->redirectToRoute('dashboard');
            }
        }

        return $this->render('servers/__serverEnterpriseModal.html.twig', ['form' => $form->createView(), 'title' => $title, 'server' => $server]);
    }

    /**
     * @Route("/server/add-user", name="server_add_user")
     */
    public function roomAddUser(Request $request, InviteService $inviteService, ServerService $serverService, TranslatorInterface $translator, UserCreatorService $userCreatorService)
    {
        $newMember = [];
        $server = $this->doctrine->getRepository(Server::class)->findOneBy(['id' => $request->get('id')]);
        if ($server->getAdministrator() !== $this->getUser()) {
            $this->addFlash('danger', $translator->trans('Keine Berechtigung'));
            return $this->redirectToRoute('dashboard');
        }
        $form = $this->createForm(NewPermissionType::class, $newMember, ['action' => $this->generateUrl('server_add_user', ['id' => $server->getId()])]);
        $form->handleRequest($request);

        $errors = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $newMembers = $form->getData();
            $lines = explode("\n", $newMembers['member']);

            if (sizeof($lines) > 0) {
                $em = $this->doctrine->getManager();
                foreach ($lines as $line) {
                    $newMember = trim($line);
                    $user = $userCreatorService->createUser($newMember, $newMember, '', '');
                    $user->addServer($server);
                    $em->persist($user);
                    $serverService->addPermission($server, $user);
                }
                $em->flush();
                $snack = 'Berechtigung hinzugefügt';
                $this->addFlash('success', $snack);
                return $this->redirectToRoute('dashboard');
            }
        }
        $title = $translator->trans('Organisator zu Server hinzufügen');

        return $this->render('servers/permissionModal.html.twig', ['form' => $form->createView(), 'title' => $title, 'users' => $server->getUser(), 'server' => $server]);
    }

    /**
     * @Route("/server/user/remove", name="server_user_remove")
     */
    public function serverUserRemove(Request $request, TranslatorInterface $translator)
    {

        $server = $this->doctrine->getRepository(Server::class)->findOneBy(['id' => $request->get('id')]);
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['id' => $request->get('user')]);
        $snack = $translator->trans('Keine Berechtigung');
        if ($server->getAdministrator() === $this->getUser() || $user === $this->getUser()) {
            $server->removeUser($user);
            $em = $this->doctrine->getManager();
            $em->persist($server);
            $em->flush();
            $snack = $translator->trans('Berechtigung gelöscht');
        }
        $this->addFlash('success', $snack);
        return $this->redirectToRoute('dashboard');
    }

    /**
     * @Route("/server/delete", name="server_delete")
     */
    public function serverDelete(Request $request, TranslatorInterface $translator, ServerService $serverService)
    {

        $server = $this->doctrine->getRepository(Server::class)->findOneBy(['id' => $request->get('id')]);
        $snack = $translator->trans('Keine Berechtigung');
        if ($server->getAdministrator() === $this->getUser()) {
            $em = $this->doctrine->getManager();
            $groupServer = $this->doctrine->getRepository(KeycloakGroupsToServers::class)->findBy(['server' => $server]);
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
        $this->addFlash('success', $snack);
        return $this->redirectToRoute('dashboard');
    }

    /**
     * @Route("/server/check/email", name="server_check_email")
     */
    public function servercheckEmail(Request $request, TranslatorInterface $translator, MailerService $mailerService)
    {

        $color = 'success';
        $snack = $translator->trans('SMTP Einstellungen korrekt. Sie sollten in Kürze eine Email erhalten');
        $server = $this->doctrine->getRepository(Server::class)->find($request->get('id'));

        if (!$server || $server->getAdministrator() != $this->getUser()) {
            $color = 'danger';
            $snack = $translator->trans('Fehler, der Server ist nicht registriert');
        } else {
            try {
                $transport = null;
                if ($server->getSmtpHost()) {
                    $this->logger->info('Build new Transport: ' . $server->getSmtpHost());
                    if ($server->getSmtpUsername()) {
                        $this->logger->info('The Transport is new and we take him');
                        $dsn = 'smtp://' . $server->getSmtpUsername() . ':' . $server->getSmtpPassword() . '@' . $server->getSmtpHost() . ':' . $server->getSmtpPort() . '?verify_peer=false';
                    } else {
                        $dsn = 'smtp://' . $server->getSmtpHost() . ':' . $server->getSmtpPort() . '?verify_peer=false';
                    }
                } else {
                    $snack = $translator->trans('Fehler') . ': SMTP-Host';
                    $color = 'danger';
                    $this->addFlash($color, $snack);
                    return $this->redirectToRoute('dashboard');
                }
                $transport = Transport::fromDsn($dsn);
                $message = (new Email())
                    ->subject($translator->trans('Testmail vom Jitsi-Admin') . ' | ' . $server->getUrl())
                    ->from(new Address($server->getSmtpEmail(), $server->getSmtpSenderName()))
                    ->to($this->getUser()->getEmail())
                    ->html(
                        '<h1>' . $translator->trans('Sie haben einen SMTP-Server für Ihren Jitsi-Server erfolgreich eingerichtet') . '</h1>'
                        . $server->getSmtpHost() . '<br>'
                        . $server->getSmtpEmail() . '<br>'
                        . $server->getSmtpSenderName() . '<br>'
                    );
                $transport->send($message);
            } catch (\Exception $e) {
                $color = 'danger';
                $snack = $translator->trans('Fehler') . ': ' . $e->getMessage();
            }
        }
        $this->addFlash($color, $snack);
        return $this->redirectToRoute('dashboard');
    }
}
