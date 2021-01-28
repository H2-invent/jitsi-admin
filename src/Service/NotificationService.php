<?php
/**
 * Created by PhpStorm.
 * User: Emanuel
 * Date: 03.10.2019
 * Time: 19:01
 */

namespace App\Service;

use App\Entity\Rooms;
use App\Entity\Server;
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
        $this->twig = $environment;
    }

    function createIcs (Rooms $rooms, User $user, $url ,$method = 'REQUEST') {
        $this->ics = new IcsService();
        $this->ics->setMethod($method);
        if ($rooms->getModerator() !== $user) {
            $organizer = $rooms->getModerator()->getEmail();
        } else{
            $organizer = 'noreply@jitsi-admin.de';
            $this->ics->setIsModerator(true);
        }
        $this->ics->add(
            array(
                'uid'=>md5($rooms->getUid()),
                'location' => 'Jitsi Konferenz',
                'description' =>  'Sie wurden zu einer Videokonferenz auf dem Jitsi Server ' . $rooms->getServer()->getUrl() . ' hinzugefügt.\n\nÜber den beigefügten Link können Sie ganz einfach zur Videokonferenz beitreten.\nName: ' . $rooms->getName() . '\nModerator: ' . $rooms->getModerator()->getFirstName() .' ' . $rooms->getModerator()->getLastName() . '\n\nFolgende Daten benötigen Sie um der Konferenz beizutreten:\nKonferenz ID: ' . $rooms->getUid() . '\nIhre Email Adresse: ' . $user->getEmail() . '\n\n' . $url . '\n\nSie erhalten diese E-Mail, weil Sie zu einer Videokonferenz eingeladen wurden.',
                'dtstart' => $rooms->getStart()->format('Ymd')."T".$rooms->getStart()->format("His"),
                'dtend' => $rooms->getEnddate()->format('Ymd')."T".$rooms->getEnddate()->format("His"),
                'summary' => $rooms->getName(),
                'sequence' => $rooms->getSequence(),
                'organizer' => $organizer,
                'attendee' => $user->getEmail(),
            )
        );
        return $this->ics->toString();
    }

    function sendNotification($content, $subject, User $user, Server $server, $attachement = array())
    {

        $this->mailer->sendEmail(
            $user->getEmail(),
            $subject,
            $content,
            $server,
            $attachement
        );


        return true;
    }


    function sendCron($content, $subject, User $user, Server $server)
    {
        $this->mailer->sendEmail(
            $user->getEmail(),
            $subject,
            $content,
            $server
        );

        return true;
    }


}
