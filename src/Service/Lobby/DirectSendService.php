<?php

namespace App\Service\Lobby;

use App\Entity\User;
use App\Service\RoomService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mercure\Exception\RuntimeException;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class DirectSendService
{
    private $publisher;
    private $urlgenerator;
    private $parameterBag;
    private $logger;
    private $translator;
    private $roomService;
    private $twig;

    public function __construct(
        Environment           $environment,
        HubInterface          $publisher,
        RoomService           $roomService,
        UrlGeneratorInterface $urlGenerator,
        ParameterBagInterface $parameterBag,
        LoggerInterface       $logger,
        TranslatorInterface   $translator
    )
    {
        $this->publisher = $publisher;
        $this->urlgenerator = $urlGenerator;
        $this->parameterBag = $parameterBag;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->roomService = $roomService;
        $this->twig = $environment;
    }

    public function setMercurePublisher(HubInterface $hub)
    {
        $this->publisher = $hub;
    }

    public function sendSnackbar($topic, $text, $color, $closeAfterMs = null)
    {
        $data = [
            'type' => 'snackbar',
            'message' => $text,
            'color' => $color,

        ];
        if ($closeAfterMs){
            $data[ 'closeAfter'] = $closeAfterMs;
        }
        $update = new Update($topic, json_encode($data));
        return $this->publisher->publish($update);
    }

    public function sendMessage($topic, $message, string $from)
    {
        $data = [
            'type' => 'message',
            'message' => $message,
            'from' => $from
        ];
        $update = new Update($topic, json_encode($data));
        return $this->publisher->publish($update);
    }

    public function sendReloadPage($topic, $timeout)
    {
        $data = [
            'type' => 'reload',
            'timeout' => $timeout,
        ];
        $update = new Update($topic, json_encode($data));
        return $this->publisher->publish($update);
    }

    public function sendBrowserNotification($topic, $title, $message, $pushMessage, $id, $color, $closeAfterMs = null)
    {
        $data = [
            'type' => 'notification',
            'title' => $title,
            'message' => $message,
            'pushNotification' => $pushMessage,
            'messageId' => $id,
            'color' => $color,
        ];
        if ($closeAfterMs){
            $data[ 'closeAfter'] = $closeAfterMs;
        }
        $update = new Update($topic, json_encode($data));
        return $this->publisher->publish($update);
    }

    public function sendCleanBrowserNotification($topic, $id)
    {
        $data = [
            'type' => 'cleanNotification',
            'messageId' => $id,
        ];
        $update = new Update($topic, json_encode($data));
        return $this->publisher->publish($update);
    }

    public function sendModal($topic, $content)
    {

        $data = [
            'type' => 'modal',
            'content' => $content,

        ];
        $update = new Update($topic, json_encode($data));
        return $this->sendUpdate($update);
    }

    public function sendRedirect($topic, $url, $timeout = 1000)
    {
        $data = [
            'type' => 'redirect',
            'url' => $url,
            'timeout' => $timeout,
        ];
        $update = new Update($topic, json_encode($data));
        return $this->sendUpdate($update);
    }

    public function sendEndMeeting($topic, $url, $timeout = 1000)
    {
        $data = [
            'type' => 'endMeeting',
            'url' => $url,
            'timeout' => $timeout
        ];
        $update = new Update($topic, json_encode($data));
        return $this->sendUpdate($update);
    }

    public function sendNewJitsiMeeting($topic, $options)
    {
        $data = [
            'type' => 'newJitsi',
            'options' => $options,
        ];
        $update = new Update($topic, json_encode($data));
        return $this->sendUpdate($update);
    }

    public function sendRefresh($topic, $url)
    {
        $data = [
            'type' => 'refresh',
            'reloadUrl' => $url,
        ];
        $update = new Update($topic, json_encode($data));
        return $this->sendUpdate($update);
    }

    public function sendCallAdhockmeeding($title, $topic, $message, $pushMesage, $time, $id)
    {
        $data = [
            'type' => 'call',
            'title' => $title,
            'message' => $message,
            'pushMessage' => $pushMesage,
            'time' => $time,
            'color' => 'success',
            'messageId' => $id
        ];
        $update = new Update($topic, json_encode($data));
        return $this->sendUpdate($update);
    }

    public function sendRefreshDashboardToUser(User $user)
    {
        $topic = 'personal/' . $user->getUid();
        $this->sendRefreshDashboard($topic);
    }

    public function sendRefreshDashboard($topic)
    {
        $data = [
            'type' => 'refreshDashboard',
        ];
        $update = new Update($topic, json_encode($data));
        return $this->sendUpdate($update);
    }


    private function sendUpdate(Update $update)
    {
        try {
            $this->logger->debug('send Message via Websocket:', ['topic' => $update->getTopics(), 'data' => $update->getData()]);
            $res = $this->publisher->publish($update);
            return true;
        } catch (RuntimeException $e) {
            $this->logger->error('Mercure Hub not available: ' . $e->getMessage());
            return false;
        }
    }
}
