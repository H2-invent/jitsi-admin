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


class NotificationService
{
    private $mailer;
    private $parameterBag;
    private $ics;
    private $twig;

    public function __construct(MailerService $mailerService, ParameterBagInterface $parameterBag, IcsService $icsService, Environment $environment)
    {
        $this->mailer = $mailerService;
        $this->parameterBag = $parameterBag;
        $this->ics = $icsService;
        $this->twig = $environment;
    }

    function sendNotification($content, $subject, User $user, Rooms $rooms, $url)
    {
        $attachement = array();
        $summay = $this->twig->render('email/addUserIcs.html.twig', ['user' => $user, 'room' => $rooms, 'url'=>$url]);
        $this->ics->add(
            array(
                'location' => 'Jitsi Konferenz',
                'description' => $summay,
                'dtstart' => $rooms->getStart()->format('Ymd')."T".$rooms->getStart()->format("His"),
                'dtend' => $rooms->getEnddate()->format('Ymd')."T".$rooms->getEnddate()->format("His"),
                'summary' => $rooms->getName(),
                'url' => $url
            )
        );
        $attachement[] = array('type' => 'text/calendar', 'filename' => $rooms->getName() . '.ics', 'body' => $this->ics->toString());
        $this->mailer->sendEmail(
            $this->parameterBag->get('registerEmailName'),
            $this->parameterBag->get('defaultEmail'),
            $user->getEmail(),
            $subject,
            $content,
            $attachement
        );


        return true;
    }


}
