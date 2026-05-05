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
use App\Service\Jigasi\JigasiService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class NotificationService
{
    private $mailer;

    private $ics;

    private $translator;
    private $jigasiService;
    public function __construct(MailerService $mailerService, TranslatorInterface $translator, JigasiService $jigasiService)
    {
        $this->mailer = $mailerService;

        $this->translator = $translator;
        $this->jigasiService = $jigasiService;
    }

    function createIcs(Rooms $rooms, User $user, $url, $method = 'REQUEST')
    {
        $this->ics = new IcsService();

        if ($rooms->getModerator() === $user && $method!=='CANCEL') {
            $method = 'PUBLISH';

        }
        $this->ics->setMethod($method);
        $organizer = $rooms->getModerator()->getEmail();


        $description = $this->translator->trans("Sie wurden zu einer Videokonferenz eingeladen.") .
            "\n\n" .
            $this->translator->trans("Über den beigefügten Link können Sie ganz einfach zur Videokonferenz beitreten.\nName: {name} \nModerator: {moderator} ", ["{name}" => $rooms->getName(), "{moderator}" => $rooms->getModerator()->getFirstName() . " " . $rooms->getModerator()->getLastName()])
            . ($rooms->getAgenda() ? "\n\n" . $this->translator->trans("Agenda") . ":\n" . implode("\n", explode("\r\n", $rooms->getAgenda())) . "\n\n" : "\n\n") .
            $this->translator->trans("Folgende Daten benötigen Sie um der Konferenz beizutreten:\nKonferenz ID: {id} \nIhre E-Mail-Adresse: {email}", ["{id}" => $rooms->getUid(), "{email}" => $user->getEmail()])
            . "\n\n" .
            $url .
            "\n\n" .
            $this->translator->trans("Sie erhalten diese E-Mail, weil Sie zu einer Videokonferenz eingeladen wurden.");


        if ($this->jigasiService->getRoomPin($rooms) && $this->jigasiService->getNumber($rooms)) {
            $description = $description . "\n\n\n" . $this->translator->trans("email.sip.text") . "\n";

            foreach ($this->jigasiService->getNumber($rooms) as $key => $value) {
                foreach ($value as $data) {
                    $description = $description
                        . sprintf("(%s) %s %s: %s# (%s,,%s#) \n", $key, $data, $this->translator->trans("email.sip.pin"), $this->jigasiService->getRoomPin($rooms), $data, $this->jigasiService->getRoomPin($rooms));
                }
            }
        }

        $this->ics->addEvent(
            [
                'uid' => md5($rooms->getUid()).'@'.parse_url($rooms->getHostUrl(), PHP_URL_HOST),
                'location' => $this->translator->trans('meetling'),
                'description' => $description,
                'dtstart' => $rooms->getStartUtc(),
                'dtend' => $rooms->getEndDateUtc(),
                'summary' => $rooms->getName(),
                'sequence' => $rooms->getSequence(),
                'organizer' => 'MAILTO:' . $organizer,
                'organizerEmail'=>$rooms->getModerator()->getEmail(),
                'organizerName'=>$rooms->getModerator()->getFirstName() .' '. $rooms->getModerator()->getLastName(),
                'attendee' => $user->getEmail(),
                'transp' => 'OPAQUE',
                'url'=>$url,
                'class' => 'public'
            ]
        );

        return $this->ics->toString();
    }

    function sendNotification($content, $subject, User $user, Server $server, Rooms $rooms = null, $attachement = []): bool
    {
        return $this->mailer->sendEmail(
            $user,
            $subject,
            $content,
            $server,
            $rooms ? $rooms->getModerator()->getEmail() : null,
            $rooms,
            $attachement
        );
    }


    function sendCron($content, $subject, User $user, Server $server, Rooms $rooms): bool
    {
        return $this->mailer->sendEmail(
            $user,
            $subject,
            $content,
            $server,
            $rooms->getModerator()->getEmail(),
            $rooms
        );
    }
}
