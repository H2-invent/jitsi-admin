<?php

namespace App\Controller;

use App\Form\Type\SecondEmailType;
use App\Form\Type\TimeZoneType;
use http\Exception\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;


class SecondEmailChangeController extends AbstractController
{
    private $logger;
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @Route("/room/secondEmail/change", name="second_email_change")
     */
    public function index(Request $request, TranslatorInterface $translator): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(SecondEmailType::class, $user, ['action' => $this->generateUrl('second_email_save')]);
        return $this->render('time_zone/index.html.twig', array(
            'form' => $form->createView(),
            'message'=>$translator->trans('secondEmail.help'),
            'title'=> $translator->trans('second.email.title')
        ));
    }

    /**
     * @Route("/room/secondEmail/save", name="second_email_save")
     */
    public function new(Request $request, TranslatorInterface $translator,LoggerInterface $logger): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(SecondEmailType::class, $user, ['action' => $this->generateUrl('second_email_save')]);
        try {
            $form->handleRequest($request);
            dump($form);
            if ($form->isSubmitted() && $form->isValid()) {
                $user = $form->getData();
                dump($user);
                if($user->getSecondEmail()){
                    foreach (explode(',',$user->getSecondEmail()) as $data){
                        if(!filter_var(trim($data), FILTER_VALIDATE_EMAIL)){
                            throw new \InvalidArgumentException('Invalid Email: '.$data);
                        }
                    }
                }
                $em = $this->getDOctrine()->getManager();
                $em->persist($user);
                $em->flush();
            }
        }
        catch (\InvalidArgumentException $exception){
            $logger->error($exception->getMessage());
            return $this->redirectToRoute('dashboard',array('snack'=>$translator->trans('Ungültige Email. Bitte überprüfen Sie ihre Emailadresse.'),'color'=>'danger'));
        }
        catch (\Exception $exception){
            $logger->error($exception->getMessage());
            return $this->redirectToRoute('dashboard',array('snack'=>$translator->trans('Fehler'),'color'=>'danger'));
        }

        return $this->redirectToRoute('dashboard',array('snack'=>$translator->trans('CC-E-Mails erfolgreich geändert auf: {secondEmails}',array('{secondEmails}'=>$user->getSecondEmail()))));
    }
}
