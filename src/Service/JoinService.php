<?php


namespace App\Service;


use App\Entity\Rooms;
use App\Entity\User;
use App\UtilsHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class JoinService
{
    private $parameterBag;
    private $em;
    private $translator;
    private $urlGenerator;
    private $roomService;
    private $response;
    private $startService;
    private $session;
    public function __construct(
        RequestStack  $requestStack,
        StartMeetingService $startMeetingService,
       private Security $security,
        RouterInterface $response,
        RoomService $roomService,
        UrlGeneratorInterface $urlGenerator,
        ParameterBagInterface $parameterBag,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator
    )
    {
        $this->parameterBag = $parameterBag;
        $this->em = $entityManager;
        $this->translator = $translator;
        $this->urlGenerator = $urlGenerator;
        $this->roomService = $roomService;
        $this->response = $response;
        $this->startService = $startMeetingService;
        $this->session = $requestStack;
    }

    public function join($search, &$snack, &$color, $appAllowed, $appKlicked, $browerAllowed, $browserKlicked)
    {
        $room = $this->em->getRepository(Rooms::class)->findOneBy(['uid' => $search['uid']]);
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $search['email']]);
        if ($room && in_array($user,$room->getUser()->toArray())) {

            if ($appAllowed && $appKlicked) {
                $type = 'a';
            } elseif ($browerAllowed && $browserKlicked) {
                $type = 'b';
            }
            $start = null;
            $now =  new \DateTime('now', new \DateTimeZone('utc'));
            $endDate = null;
            if(!$room->getPersistantRoom()){

                $start = (clone $room->getStartUtc())->modify('-30min');
                $endDate = clone $room->getEndDateUtc();

                $startPrint = $room->getTimeZone()?clone ($room->getStartUtc())->setTimeZone(new \DateTimeZone($room->getTimeZone())):$room->getStart();
                $startPrint->modify('-30min');
                $endPrint = $room->getTimeZone()?$room->getEndDateUtc()->setTimeZone(new \DateTimeZone($room->getTimeZone())):$room->getEnddate();

            }

            if (
                ($start && $start < $now && $endDate > $now)
                || UtilsHelper::isAllowedToOrganizeRoom($user,$room)
                || $room->getPersistantRoom()
                || $user->getKeycloakId()
            ) {
                if($user->getKeycloakId()){
                    return new RedirectResponse($this->urlGenerator->generate('room_join',array('room'=>$room->getId(),'t'=>$type)));
                }else{
                    if ($this->session->getCurrentRequest()){
                        $this->session->getCurrentRequest()->getSession()->set('userId',$user->getId());
                    }

                    return $this->startService->startMeeting($room, $user, $type,$search['name']);
                }

            } else {
                try {
                    $snack = $this->translator->trans('Der Beitritt ist nur von {from} bis {to} möglich',
                        array(
                            '{from}' => $startPrint->format('d.m.Y H:i T'),
                            '{to}' => $endPrint->format('d.m.Y H:i T')
                        )
                    );
                    $color = 'danger';
                } catch (\Exception $exception) {

                }
            }
        }else{
            $snack = $this->translator->trans('Fehler: Ihre E-Mail-Adresse ist nicht in der Teilnehmendenliste! Bitte kontaktieren Sie den Moderator, damit dieser Sie zu der Konferenz einlädt.');
            $color = 'danger';
        }

        return null;
    }

    /**
     * function onlyWithUserAccount
     * Return if only users with account can join the conference
     * @return boolean
     * @author Andreas Holzmann
     */
    function onlyWithUserAccount(?Rooms $room)
    {
        if ($room) {
            return $this->parameterBag->get('laF_onlyRegisteredParticipents') == 1 || //only registered Users globally set
                $room->getOnlyRegisteredUsers();
        }
        return false;
    }

    /**
     * function userAccountLogin
     * Return boolean if account must login to join the conference
     * @return boolean
     * @author Andreas Holzmann
     */
    function userAccountLogin(?Rooms $room, ?User $user)
    {
        if ($room) {
            return $user && $user->getKeycloakId() !== null; // Registered Users have to login before they can join the conference
        }
        return false;
    }

    /**
     * This Function generates te Response when the Room is a normal room with a atendece List
     * @param $type
     * @param $name
     * @param Rooms $room
     * @param User $user
     * @return RedirectResponse
     */
    private function generateResponseCommonRoom($type, $name, Rooms $room, User $user)
    {
        if ($this->onlyWithUserAccount($room) || $this->userAccountLogin($room, $user)) {
            return new RedirectResponse($this->urlGenerator->generate('room_join', ['room' => $room->getId(), 't' => $type]));
        }
        if ($room->getLobby()) {
            $res = new RedirectResponse($this->urlGenerator->generate('lobby_participants_wait', ['roomUid' => $room->getUidReal(), 'type' => $type, 'userUid' => $user->getUid()]));
        } else {
            $url = $this->roomService->join($room, $user, $type, $name);
            $res = new RedirectResponse($url);
        }

        $res->headers->setCookie(new Cookie('name', $name, (new \DateTime())->modify('+365 days')));
        return $res;
    }

    /**
     * This Function generates te Response when the Room is has no attendece list
     * @param $type
     * @param $name
     * @param Rooms $room
     * @return RedirectResponse
     */
    private function generateResponseOpenRoom($type, $name, Rooms $room)
    {
        $url = $this->urlGenerator->generate('room_waiting', array('name' => $name, 'uid' => $room->getUid(), 'type' => $type));
        $res = new RedirectResponse(($url));
        $res->headers->setCookie(new Cookie('name', $name, (new \DateTime())->modify('+365 days')));
        return $res;
    }
}