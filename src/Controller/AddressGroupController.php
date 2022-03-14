<?php

namespace App\Controller;

use App\Entity\AddressGroup;
use App\Form\Type\AddressGroupType;
use App\Service\IndexGroupsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class AddressGroupController extends AbstractController
{
    /**
     * @Route("room/address/group/new", name="address_group_new")
     */
    public function new(Request $request, TranslatorInterface $translator, IndexGroupsService $indexGroupsService): Response
    {
        $addressgroup = new AddressGroup();
        $addressgroup->setCreatedAt(new \DateTimeImmutable());
        $addressgroup->setLeader($this->getUser());
        $title = $translator->trans('Neue Kontaktgruppe erstellen');
        if ($request->get('id')) {
            $addressgroup = $this->getDoctrine()->getRepository(AddressGroup::class)->findOneBy(array('id' => $request->get('id')));
            if ($addressgroup->getLeader() != $this->getUser()) {
                throw new NotFoundHttpException('Not Found');
            }
            $title = $translator->trans('Kontaktgruppe bearbeiten');
        }
        $form = $this->createForm(AddressGroupType::class, $addressgroup, ['user' => $this->getUser(), 'action' => $this->generateUrl('address_group_new', array('id' => $addressgroup->getId()))]);
        try {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $addressgroup->setUpdatedAt(new \DateTimeImmutable());
                $addressgroup = $form->getData();
                $addressgroup->setIndexer($indexGroupsService->indexGroup($addressgroup));
                $em = $this->getDoctrine()->getManager();
                $em->persist($addressgroup);
                $em->flush();

                return $this->redirectToRoute('dashboard', array('snack' => $translator->trans('Kontaktgruppe erfolgreich angelegt')));
            }
        } catch (\Exception $e) {
            $snack = $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.');
            return $this->redirectToRoute('dashboard', array('snack' => $snack, 'color' => 'danger'));
        }

        return $this->render('address_group/index.html.twig', [
            'form' => $form->createView(),
            'title' => $title
        ]);
    }

    /**
     * @Route("room/address/group/remove", name="address_group_remove")
     */
    public function remove(Request $request, TranslatorInterface $translator): Response
    {
        $addressgroup = $this->getDoctrine()->getRepository(AddressGroup::class)->findOneBy(array('id' => $request->get('id')));
        if (!$addressgroup || $addressgroup->getLeader() != $this->getUser()) {
            throw new NotFoundHttpException('Not Found');
        }
        $em = $this->getDoctrine()->getManager();
        $em->remove($addressgroup);
        $em->flush();
        return $this->redirectToRoute('dashboard',array('snack'=>$translator->trans('Kontaktgruppe gelöscht')));
    }
}
