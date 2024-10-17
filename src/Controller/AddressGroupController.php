<?php

namespace App\Controller;

use App\Entity\AddressGroup;
use App\Form\Type\AddressGroupType;
use App\Helper\JitsiAdminController;
use App\Service\IndexGroupsService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class AddressGroupController extends JitsiAdminController
{
    #[Route(path: 'room/address/group/new', name: 'address_group_new')]
    public function new(Request $request, TranslatorInterface $translator, IndexGroupsService $indexGroupsService): Response
    {
        $addressgroup = new AddressGroup();
        $addressgroup->setCreatedAt(new \DateTimeImmutable());
        $addressgroup->setLeader($this->getUser());
        $title = $translator->trans('Neue Kontaktgruppe erstellen');
        if ($request->get('id')) {
            $addressgroup = $this->doctrine->getRepository(AddressGroup::class)->findOneBy(['id' => $request->get('id')]);
            if ($addressgroup->getLeader() !== $this->getUser()) {
                throw new NotFoundHttpException('Not Found');
            }
            $title = $translator->trans('Kontaktgruppe bearbeiten');
        }
        $form = $this->createForm(AddressGroupType::class, $addressgroup, ['user' => $this->getUser(), 'action' => $this->generateUrl('address_group_new', ['id' => $addressgroup->getId()])]);

        try {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $addressgroup->setUpdatedAt(new \DateTimeImmutable());
                $addressgroup = $form->getData();
                $addressgroup->setIndexer($indexGroupsService->indexGroup($addressgroup));
                $em = $this->doctrine->getManager();
                $em->persist($addressgroup);
                $em->flush();
                $this->addFlash('success', $translator->trans('Kontaktgruppe erfolgreich angelegt'));
                return $this->redirectToRoute('dashboard');
            }
        } catch (\Exception $e) {
            $snack = $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.');
            $this->addFlash('danger', $snack);
            return $this->redirectToRoute('dashboard');
        }

        return $this->render(
            'address_group/index.html.twig',
            [
                'form' => $form->createView(),
                'title' => $title
            ]
        );
    }

    #[Route(path: 'room/address/group/remove', name: 'address_group_remove')]
    public function remove(Request $request, TranslatorInterface $translator): Response
    {
        $addressgroup = $this->doctrine->getRepository(AddressGroup::class)->findOneBy(['id' => $request->get('id')]);
        if (!$addressgroup || $addressgroup->getLeader() != $this->getUser()) {
            throw new NotFoundHttpException('Not Found');
        }
        $em = $this->doctrine->getManager();
        $em->remove($addressgroup);
        $em->flush();
        $this->addFlash('success', $translator->trans('Kontaktgruppe gelöscht'));
        return $this->redirectToRoute('dashboard');
    }
}
