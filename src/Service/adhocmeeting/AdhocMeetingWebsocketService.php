<?php

namespace App\Service\adhocmeeting;

use App\Entity\Rooms;
use App\Entity\User;
use App\Service\Lobby\DirectSendService;
use App\Service\ThemeService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdhocMeetingWebsocketService
{
    public function __construct(

        private ParameterBagInterface  $parameterBag,
        private TranslatorInterface    $translator,
        private DirectSendService      $directSendService,
        private UrlGeneratorInterface  $urlGen,
        private ThemeService           $theme,
    )
    {

    }


    public function sendAddhocMeetingWebsocket(User $reciever, User $creator, Rooms $room): void
    {
        $topic = 'personal/' . $reciever->getUid();
        $format = '%s<br><a href="%s"  class="btn btn-sm btn-sucess ' . ($this->theme->getApplicationProperties('LAF_USE_MULTIFRAME') === 1 ? 'startIframe' : '') . '" data-roomname = "%s" ><i class="fas fa-phone" ></i > %s </a ><a class="btn btn-sm btn-danger" ><i class="fas fa-phone-slash" ></i ></a > ';
        $toastText = sprintf(
            $format,
            $this->translator->trans('addhock.notification.pushMessage', ['{name}' => $creator->getFormatedName($this->parameterBag->get('laf_showName'))]),
            $this->urlGen->generate('room_join', ['room' => $room->getId(), 't' => 'b']),
            $room->getSecondaryName() ?: $room->getName(),
            $this->translator->trans('Hier beitreten'),
        );
        $this->directSendService->sendCallAdhockmeeding(
            $this->translator->trans('addhock.notification.title'),
            $topic,
            $toastText,
            $this->translator->trans('addhock.notification.pushMessage', ['{name}' => $creator->getFormatedName($this->parameterBag->get('laf_showName'))]),
            60000,
            $room->getUid()
        );
    }
}
