<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\Type\SecondEmailType;
use App\Form\Type\TimeZoneType;
use App\Helper\JitsiAdminController;
use http\Exception\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;


class SecondEmailChangeController extends JitsiAdminController
{


    /**
     * @Route("/room/secondEmail/change", name="second_email_change")
     */
    public function index(Request $request, TranslatorInterface $translator): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(SecondEmailType::class, $user, ['action' => $this->generateUrl('second_email_save')]);
        return $this->render('time_zone/index.html.twig', array(
            'form' => $form->createView(),
            'title' => $translator->trans('second.email.title')
        ));
    }

    /**
     * @Route("/room/secondEmail/save", name="second_email_save")
     */
    public function new(Request $request, TranslatorInterface $translator, LoggerInterface $logger): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(SecondEmailType::class, $user, ['action' => $this->generateUrl('second_email_save')]);
        try {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $user = $form->getData();

                if ($user->getSecondEmail()) {
                    foreach (explode(',', $user->getSecondEmail()) as $data) {
                        if (!filter_var(trim($data), FILTER_VALIDATE_EMAIL)) {
                            throw new \InvalidArgumentException('Invalid Email: ' . $data);
                        }
                    }
                }

                $user->getProfilePicture()->setUpdatedAt(new \DateTime());
                $user->setUpdatedAt(new \DateTime());
                $em = $this->doctrine->getManager();
                $em->persist($user);
                $em->flush();
                $user = $this->getUser();
                if ($user->getProfilePicture() && !$user->getProfilePicture()->getDocumentFileName()) {
                    $user->setProfilePicture(null);
                    $em->persist($user);
                    $em->flush();
                }
            }
        } catch (\InvalidArgumentException $exception) {
            $logger->error($exception->getMessage());
            $this->addFlash('danger', $translator->trans('Ungültige Email. Bitte überprüfen Sie ihre Emailadresse.'));
            return $this->redirectToRoute('dashboard');
        } catch (\Exception $exception) {
            $logger->error($exception->getMessage());
            $this->addFlash('danger', $translator->trans('Fehler'));
            return $this->redirectToRoute('dashboard');
        }
        $this->addFlash('success', $translator->trans('CC-E-Mails erfolgreich geändert auf: {secondEmails}', array('{secondEmails}' => $user->getSecondEmail())));
        return $this->redirectToRoute('dashboard');
    }
}
