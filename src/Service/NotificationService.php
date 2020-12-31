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
        $this->ics->add(
            array(
                'location' => 'Jitsi Konferenz',
                'description' =>  'Sie wurden zu einer Videokonferenz auf dem Jitsi Server ' . $rooms->getServer()->getUrl() . 'hinzugefügt.\n\nÜber den beigefügten Link können Sie ganz einfach zur Videokonferenz beitreten.\nName: ' . $rooms->getName() . '\nModerator: ' . $rooms->getModerator()->getFirstName() .' ' . $rooms->getModerator()->getLastName() . '\n\nFolgende Daten benötigen Sie um der Konferenz beizutreten:\nIhre Email Adresse: ' . $user->getEmail() . '\nKonferenz ID: ' . $rooms->getUid() . '\n\n' . $url . '\n\nSie erhalten diese E-Mail, weil Sie zu einer Videokonferenz eingeladen wurden.',
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
