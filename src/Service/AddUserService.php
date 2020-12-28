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
use Twig\Environment;


class AddUserService
{
    private $mailer;
    private $parameterBag;
    private $twig;
    private $notificationService;

    public function __construct(MailerService $mailerService, ParameterBagInterface $parameterBag, Environment $environment, NotificationService $notificationService)
    {
        $this->mailer = $mailerService;
        $this->parameterBag = $parameterBag;
        $this->twig = $environment;
        $this->notificationService = $notificationService;
    }

    function addUser(User $user, Rooms $room)
    {
        $data = base64_encode('uid='.$room->getUid().'&email='.$user->getEmail());
        $content = $this->twig->render('email/addUser.html.twig', ['user' => $user, 'room' => $room, 'data'=>$data]);
        $subject = 'Neue Einladung zu einer Videokonferenz';
        $this->notificationService->sendNotification($content, $subject, $user);

        return true;
    }


}
