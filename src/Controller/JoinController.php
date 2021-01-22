<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\User;
use App\Form\Type\JoinViewType;
use App\Service\RoomService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class JoinController extends AbstractController
{
    /**
     * @Route("/join", name="join_index")
     */
    public function index(Request $request, TranslatorInterface $translator, RoomService $roomService,ParameterBagInterface $parameterBag)
    {
        $data = array();
        // dataStr wird mit den Daten uid und email encoded übertragen. Diese werden daraufhin als Vorgaben in das Formular eingebaut
        $dataStr = $request->get('data');
        $snack = $request->get('snack');
        $dataAll = base64_decode($dataStr);
        $data = array();
        parse_str($dataAll, $data);

        if (isset($data['email']) && isset($data['uid'])) {
            $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(['uid' => $data['uid']]);
            $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['email' => $data['email']]);
            if($room && $parameterBag->get('laF_onlyRegisteredParticipents') == 1){
                return $this->redirectToRoute('room_join', ['room' => $room->getId(), 't' => 'b']);
            }
            if ($user && $user->getKeycloakId() !== null && $room) {
                return $this->redirectToRoute('room_join', ['room' => $room->getId(), 't' => 'b']);
            }

        } else {
            $snack = 'Zugangsdaten in das Formular eingeben';
        }
        if($parameterBag->get('laF_onlyRegisteredParticipents') == 1){
            return $this->redirectToRoute('dashboard');
        }

        $form = $this->createForm(JoinViewType::class, $data);
        $form->handleRequest($request);
        $errors = array();
        if ($form->isSubmitted() && $form->isValid()) {
            $search = $form->getData();
            $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(['uid' => $search['uid']]);
            $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['email' => $search['email']]);

            if (count($errors) == 0 && $room && $user && in_array($user, $room->getUser()->toarray())) {
                $url = $roomService->join($room,$user,'b',$search['name']);
                return $this->redirect($url);
            }
            $snack = $translator->trans('Konferenz nicht gefunden. Zugangsdaten erneut eingeben');
        }

        return $this->render('join/index.html.twig', [
            'form' => $form->createView(),
            'snack' => $snack
        ]);
    }
}
