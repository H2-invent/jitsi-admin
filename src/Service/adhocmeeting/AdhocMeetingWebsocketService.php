<?php

namespace App\Service\adhocmeeting;

use App\Entity\Rooms;
use App\Entity\User;
use App\Service\Lobby\DirectSendService;
use App\Service\Theme\ThemeService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdhocMeetingWebsocketService
{
    public function __construct(

        private ParameterBagInterface $parameterBag,
        private TranslatorInterface   $translator,
        private DirectSendService     $directSendService,
        private UrlGeneratorInterface $urlGen,
        private ThemeService          $theme,
    )
    {

    }

//todo umbau auf dialog kein toast mehr
    public function sendAddhocMeetingWebsocket(User $reciever, User $creator, Rooms $room): void
    {
        $topic = 'personal/' . $reciever->getUid();
        $header = $this->translator->trans('addhock.notification.title');
        $text = $this->translator->trans('addhock.notification.pushMessage', ['{name}' => $creator->getFormatedName($this->parameterBag->get('laf_showName'))]);
        $dialogType = 'question';
        $button = [
            [
                'class' => 'btn btn-success ' . ($this->theme->getApplicationProperties('LAF_USE_MULTIFRAME') == 1 ? 'startIframe' : ''),
                'text' => '<i class="fas fa-phone" ></i > ' . $this->translator->trans('Hier beitreten'),
                'link' => $this->urlGen->generate('room_join', ['room' => $room->getId(), 't' => 'b']),
                'data' =>
                    [
                        'roomname' => $room->getSecondaryName() ?: $room->getName()
                    ]
            ],
            [
                'class' => 'btn btn-danger ',
                'text' => '<i class="fas fa-phone-slash" ></i ></a > ',
                'data' => [],
            ]
        ];

//        $format = '%s
//<br><a href="%s"  class="btn btn-sm btn-sucess ' . ($this->theme->getApplicationProperties('LAF_USE_MULTIFRAME') === 1 ? 'startIframe' : '') . '" data-roomname = "%s" ><i class="fas fa-phone" ></i > %s </a ><a class="btn btn-sm btn-danger" ><i class="fas fa-phone-slash" ></i ></a > ';
//        $toastText = sprintf(
//            $format,
//            $this->translator->trans('addhock.notification.pushMessage', ['{name}' => $creator->getFormatedName($this->parameterBag->get('laf_showName'))]),
//            $this->urlGen->generate('room_join', ['room' => $room->getId(), 't' => 'b']),
//            $room->getSecondaryName() ?: $room->getName(),
//            $this->translator->trans('Hier beitreten'),
//        );
        $this->directSendService->sendDialog(
            $topic,
            $header,
            $text,
            $dialogType,
            $button
        );
        $this->directSendService->sendBrowserPush(
            $topic,
            $header,
            $text,
            md5(uniqid())
        );

        $this->directSendService->sendPlaySound(
            $topic,
            'caller',
            md5(uniqid())
        );
    }
}
