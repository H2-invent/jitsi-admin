<?php

namespace App\Service\Lobby;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\Exception\RuntimeException;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class DirectSendService
{
    private $publisher;
    private $logger;

    public function __construct(
        HubInterface          $publisher,
        LoggerInterface       $logger,
    )
    {
        $this->publisher = $publisher;
        $this->logger = $logger;
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

    public function sendDialog($topic, $header, $text, $type='question', $buttons=[])
    {
        $data = [
            'type' => 'dialog',
            'header' => $header,
            'text' => $text,
            'buttons' => $buttons,
            'dialogType' => $type

        ];

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

    public function sendBrowserPush($topic, $title,  $pushMessage, $id)
    {
        $data = [
            'type' => 'browserPush',
            'title' => $title,
            'pushNotification' => $pushMessage,
            'messageId' => $id,
        ];
        $update = new Update($topic, json_encode($data));
        return $this->publisher->publish($update);
    }
    public function sendPlaySound($topic, $soundName,  $id)
    {
        $data = [
            'type' => 'playSound',
            'soundName' => $soundName,
            'messageId' => $id,
        ];
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
