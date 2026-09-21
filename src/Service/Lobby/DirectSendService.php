<?php

namespace App\Service\Lobby;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\Exception\RuntimeException;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class DirectSendService
{
    private HubInterface $publisher;
    private LoggerInterface $logger;

    public function __construct(
        HubInterface          $publisher,
        LoggerInterface       $logger,
    )
    {
        $this->publisher = $publisher;
        $this->logger = $logger;
    }

    public function setMercurePublisher(HubInterface $hub): void
    {
        $this->publisher = $hub;
    }

    /**
     * @param string $topic
     * @param string $text
     * @param string $color
     * @param int|null $closeAfterMs
     */
    public function sendSnackbar($topic, $text, $color, $closeAfterMs = null): string
    {
        $data = [
            'type' => 'snackbar',
            'message' => $text,
            'color' => $color,

        ];
        if ($closeAfterMs){
            $data[ 'closeAfter'] = $closeAfterMs;
        }
        $update = new Update($topic, (string) json_encode($data));
        return $this->publisher->publish($update);
    }

    /**
     * @param string $topic
     * @param string $header
     * @param string $text
     * @param string $type
     * @param array<int, array<string, mixed>> $buttons
     */
    public function sendDialog($topic, $header, $text, $type='question', $buttons=[]): string
    {
        $data = [
            'type' => 'dialog',
            'header' => $header,
            'text' => $text,
            'buttons' => $buttons,
            'dialogType' => $type

        ];

        $update = new Update($topic, (string) json_encode($data));
        return $this->publisher->publish($update);
    }

    /**
     * @param string $topic
     * @param string $message
     */
    public function sendMessage($topic, $message, string $from): string
    {
        $data = [
            'type' => 'message',
            'message' => $message,
            'from' => $from
        ];
        $update = new Update($topic, (string) json_encode($data));
        return $this->publisher->publish($update);
    }

    /**
     * @param string $topic
     * @param mixed $timeout
     */
    public function sendReloadPage($topic, $timeout): string
    {
        $data = [
            'type' => 'reload',
            'timeout' => $timeout,
        ];
        $update = new Update($topic, (string) json_encode($data));
        return $this->publisher->publish($update);
    }

    /**
     * @param string $topic
     * @param string $title
     * @param string $message
     * @param string $pushMessage
     * @param string|int $id
     * @param string $color
     * @param int|null $closeAfterMs
     */
    public function sendBrowserNotification($topic, $title, $message, $pushMessage, $id, $color, $closeAfterMs = null): string
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
        $update = new Update($topic, (string) json_encode($data));
        return $this->publisher->publish($update);
    }

    /**
     * @param string $topic
     * @param string $title
     * @param string $pushMessage
     * @param string|int $id
     */
    public function sendBrowserPush($topic, $title,  $pushMessage, $id): string
    {
        $data = [
            'type' => 'browserPush',
            'title' => $title,
            'pushNotification' => $pushMessage,
            'messageId' => $id,
        ];
        $update = new Update($topic, (string) json_encode($data));
        return $this->publisher->publish($update);
    }
    /**
     * @param string $topic
     * @param string $soundName
     * @param string|int $id
     */
    public function sendPlaySound($topic, $soundName,  $id): string
    {
        $data = [
            'type' => 'playSound',
            'soundName' => $soundName,
            'messageId' => $id,
        ];
        $update = new Update($topic, (string) json_encode($data));
        return $this->publisher->publish($update);
    }
    /**
     * @param string $topic
     * @param string|int $id
     */
    public function sendCleanBrowserNotification($topic, $id): string
    {
        $data = [
            'type' => 'cleanNotification',
            'messageId' => $id,
        ];
        $update = new Update($topic, (string) json_encode($data));
        return $this->publisher->publish($update);
    }

    /**
     * @param string $topic
     * @param string $content
     */
    public function sendModal($topic, $content): bool
    {

        $data = [
            'type' => 'modal',
            'content' => $content,

        ];
        $update = new Update($topic, (string) json_encode($data));
        return $this->sendUpdate($update);
    }

    /**
     * @param string $topic
     * @param string $url
     * @param mixed $timeout
     */
    public function sendRedirect($topic, $url, $timeout = 1000): bool
    {
        $data = [
            'type' => 'redirect',
            'url' => $url,
            'timeout' => $timeout,
        ];
        $update = new Update($topic, (string) json_encode($data));
        return $this->sendUpdate($update);
    }

    /**
     * @param string $topic
     * @param string $url
     * @param mixed $timeout
     */
    public function sendEndMeeting($topic, $url, $timeout = 1000): bool
    {
        $data = [
            'type' => 'endMeeting',
            'url' => $url,
            'timeout' => $timeout
        ];
        $update = new Update($topic, (string) json_encode($data));
        return $this->sendUpdate($update);
    }

    /**
     * @param string $topic
     * @param array<string, mixed> $options
     */
    public function sendNewJitsiMeeting($topic, $options): bool
    {
        $data = [
            'type' => 'newJitsi',
            'options' => $options,
        ];
        $update = new Update($topic, (string) json_encode($data));
        return $this->sendUpdate($update);
    }

    /**
     * @param string $topic
     * @param string $url
     */
    public function sendRefresh($topic, $url): bool
    {
        $data = [
            'type' => 'refresh',
            'reloadUrl' => $url,
        ];
        $update = new Update($topic, (string) json_encode($data));
        return $this->sendUpdate($update);
    }

    /**
     * @param string $title
     * @param string $topic
     * @param string $message
     * @param string $pushMesage
     * @param int|string $time
     * @param string|int $id
     */
    public function sendCallAdhockmeeding($title, $topic, $message, $pushMesage, $time, $id): bool
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
        $update = new Update($topic, (string) json_encode($data));
        return $this->sendUpdate($update);
    }

    public function sendRefreshDashboardToUser(User $user): void
    {
        $topic = 'personal/' . $user->getUid();
        $this->sendRefreshDashboard($topic);
    }

    /**
     * @param string $topic
     */
    public function sendRefreshDashboard($topic): bool
    {
        $data = [
            'type' => 'refreshDashboard',
        ];
        $update = new Update($topic, (string) json_encode($data));
        return $this->sendUpdate($update);
    }


    private function sendUpdate(Update $update): bool
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
