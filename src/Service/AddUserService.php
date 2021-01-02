<?php
/**
 * Created by PhpStorm.
 * User: Emanuel
 * Date: 03.10.2019
 * Time: 19:01
 */

namespace App\Service;

use App\Entity\Rooms;
use App\Entity\User;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;


class AddUserService
{
    private $mailer;
    private $parameterBag;
    private $twig;
    private $notificationService;
    private $url;

    public function __construct(MailerService $mailerService, ParameterBagInterface $parameterBag, Environment $environment, NotificationService $notificationService, UrlGeneratorInterface $urlGenerator)
    {
        $this->mailer = $mailerService;
        $this->parameterBag = $parameterBag;
        $this->twig = $environment;
        $this->notificationService = $notificationService;
        $this->url = $urlGenerator;
    }

    function generateUrl (Rooms $room, User $user) {

        $data = base64_encode('uid='.$room->getUid().'&email='.$user->getEmail());
        $url = $this->url->generate('join_index',['data'=>$data],UrlGeneratorInterface::ABSOLUTE_URL);
        return $url;
    }

    function addUser(User $user, Rooms $room)
    {
        $url = $this->generateUrl($room,$user);
        $content = $this->twig->render('email/addUser.html.twig', ['user' => $user, 'room' => $room, 'url'=>$url]);
        $subject = 'Neue Einladung zu einer Videokonferenz';
        $this->notificationService->sendNotification($content, $subject, $user, $room, $url);

        return true;
    }

    function editRoom(User $user, Rooms $room)
    {
        $url = $this->generateUrl($room,$user);
        $content = $this->twig->render('email/editRoom.html.twig', ['user' => $user, 'room' => $room, 'url'=>$url]);
        $subject = 'Videokonferenz wurde bearbeitet';
        $this->notificationService->sendNotification($content, $subject, $user, $room, $url);

        return true;
    }

    function notifyUser(User $user, Rooms $room)
    {
        $url = $this->generateUrl($room,$user);
        $content = $this->twig->render('email/rememberUser.html.twig', ['user' => $user, 'room' => $room, 'url'=>$url]);
        $subject = 'Videokonferenz ' . $room->getName() . ' startet gleich';
        $this->notificationService->sendCron($content, $subject, $user, $room, $url);

        return true;
    }


}
