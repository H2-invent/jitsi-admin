<?php

namespace App\Controller;

use App\Form\Type\HotStandbyType;
use App\Helper\JitsiAdminController;
use App\Service\Theme\ThemeService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

final class HotStandbyController extends JitsiAdminController
{
    #[\Symfony\Component\Routing\Attribute\Route(path: '/room/hotstandby/save', name: 'hotstandby_save')]
    public function save(Request $request, ThemeService $themeService): RedirectResponse
    {
        if (!$themeService->getApplicationProperties('LAF_SHOW_HOTSTANDBY')) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->getUser();
        $form = $this->createForm(HotStandbyType::class, $user, ['action' => $this->generateUrl('hotstandby_save')]);

        try {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $user = $form->getData();
                $user->setUpdatedAt(new \DateTimeImmutable());

                $em = $this->doctrine->getManager();
                $em->persist($user);
                $em->flush();

                $this->addFlash('success', $this->translator->trans('Hot Standby Einstellung erfolgreich gespeichert'));
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            $this->addFlash('danger', $this->translator->trans('Fehler beim Speichern der Hot Standby Einstellung'));
        }

        return $this->redirectToRoute('dashboard');
    }
}
