<?php

namespace App\Controller;

use App\Entity\ProtocolTemplate;
use App\Entity\Server;
use App\Entity\Star;
use App\Form\ProtocolTemplateType;
use App\Helper\JitsiAdminController;
use App\Service\AdminService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdminController extends JitsiAdminController
{
    #[Route(path: '/admin/server/{server}', name: 'admin_server')]
    public function server(
        ParameterBagInterface             $parameterBag,
        #[MapEntity(id: 'server')] Server $server,
        AdminService                      $adminService,
        HttpClientInterface               $httpClient,
        TranslatorInterface               $translator
    ): Response {
        $countPart = 0;
        foreach ($server->getRooms() as $room) {
            $countPart = $countPart + count($room->getUser());
        }

        if (!in_array($this->getUser(), $server->getUser()->toArray())) {
            $this->addFlash('danger', $translator->trans('Fehler, Der Server wurde nicht gefunden'));
            return $this->redirectToRoute('dashboard');
        }


        $chart     = $adminService->createChart($server);
        $lastStars = $this->doctrine->getRepository(Star::class)->findBy(['server' => $server], ['createdAt' => 'DESC'], 5);
        $average   = 0;
        foreach ($lastStars as $data) {
            $average += $data->getStar();
        }
        if (sizeof($lastStars) > 0) {
            $average /= sizeof($lastStars);
        }

        return $this->render(
            'admin/modalChart.html.twig',
            [
                'server'      => $server,
                'countPart'   => $countPart,
                'chart'       => $chart,
                'lastAverage' => $average
            ]
        );
    }

    #[Route(path: '/admin/server/{server}/protocol/template', name: 'admin_protocol_template')]
    public function protocolTemplate(
        #[MapEntity(id: 'server')] Server $server,
        Request                           $request,
    ): Response
    {
        if ($server->getAdministrator() !== $this->getUser()) {
            $this->addFlash('danger', $this->translator->trans('Keine Berechtigung'));
            return $this->redirectToRoute('dashboard');
        }

        $template = $server->getProtocolTemplate() ?? new ProtocolTemplate()->setServer($server);
        $form = $this->createForm(
            ProtocolTemplateType::class,
            $template,
            ['action' => $this->generateUrl('admin_protocol_template', ['server' => $server->getId()])]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->doctrine->getManager();
            $em->persist($template);
            $em->flush();
            $this->addFlash('success', $this->translator->trans('Ihre Eingabe wurde Erfolgreich gespeichert.'));
            return $this->redirectToRoute('dashboard');
        }

        if ($form->isSubmitted()) {
            // The modal is submitted as a regular (non-AJAX) form, so validation errors
            // (e.g. logo too large) are reported via flash message on the dashboard.
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
            return $this->redirectToRoute('dashboard');
        }

        return $this->render(
            'admin/protocolTemplateModal.html.twig',
            [
                'form'     => $form,
                'title'    => $this->translator->trans('Protokoll-Template'),
                'server'   => $server,
                'template' => $template,
            ]
        );
    }
}
