<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\Subscriber;
use App\Entity\User;
use App\Form\Type\PublicRegisterType;
use App\Service\PexelService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ShareLinkController extends AbstractController
{
    private $em;
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @Route("/room/share/link/{id}", name="share_link")
     * @ParamConverter("rooms")
     */
    public function index(Rooms  $rooms): Response
    {
       if(!$rooms || !$rooms->getModerator() == $this->getUser() || $rooms->getPublic() != true){
           throw new NotFoundHttpException('Not found');
       }
   return $this->render('share_link/__shareLinkModal.html.twig',array('room'=>$rooms));

    }
    /**
     * @Route("/subscribe/participant/{uid}", name="public_subscribe_participant")
     * @ParamConverter("rooms", options={"mapping": {"uid": "uidParticipant"}})
     */
    public function participants(Request  $request, Rooms  $rooms, TranslatorInterface $translator, PexelService $pexelService): Response
    {
        $data = array('email'=>'');
        $form = $this->createForm(PublicRegisterType::class, $data);
        $form->handleRequest($request);
        $errors = array();
        $snack= $translator->trans('Bitte geben Sie ihre Daten ein');
        $color= 'success';
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        $subscriber = $this->getDoctrine()->getRepository(Subscriber::class)->findOneBy(array('room'=>$rooms,'user'=>$user));
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            if(!filter_var($data['email'], FILTER_VALIDATE_EMAIL)){
                $snack = $translator->trans('Ungültige Email. Bitte überprüfen Sie ihre Emailadresse.');
                $color= 'danger';
            }elseif (in_array($rooms,$user->getRooms()->toArray()))

                $snack = $translator->trans('Sie haben sich bereits angemeldet.');
                $color= 'danger';
            }
            elseif(!$user){
                $user = new User();
                $user->setEmail($data['email']);
            }

            if($subscriber){
                $snack = $translator->trans('Sie haben sich bereits angemeldet. Bite bestätigen sie noch ihre Anmeldung durch klick auf den Link in der Email.');
                $color= 'danger';
            }

            $snack = $translator->trans('Vielen Dank für die Anmeldung');


        $server = $rooms->getServer();
        $image = $pexelService->getImageFromPexels();
        return $this->render('share_link/subscribe.html.twig', [
            'form' => $form->createView(),
            'snack' => $snack,
            'server' => $server,
            'image' => $image,
            'room'=>$rooms,
        ]);
    }
    /**
     * @Route("/subscribe/moderator/{uid}", name="public_subscribe_moderator")
     * @ParamConverter("rooms", options={"mapping": {"uid": "uidModerator"}})
     */
    public function moderaror(Rooms  $rooms): Response
    {
        dump($rooms);
    }
}
