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

    public function sendSnackbar(string $topic, string $text, string $color, ?int $closeAfterMs = null): string
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
     * @param array<int, array<string, mixed>> $buttons
     */
    public function sendDialog(string $topic, string $header, string $text, string $type='question', array $buttons=[]): string
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

    public function sendMessage(string $topic, string $message, string $from): string
    {
        $data = [
            'type' => 'message',
            'message' => $message,
            'from' => $from
        ];
        $update = new Update($topic, (string) json_encode($data));
        return $this->publisher->publish($update);
    }

    public function sendReloadPage(string $topic, mixed $timeout): string
    {
        $data = [
            'type' => 'reload',
            'timeout' => $timeout,
        ];
        $update = new Update($topic, (string) json_encode($data));
        return $this->publisher->publish($update);
    }

    public function sendBrowserNotification(string $topic, string $title, string $message, string $pushMessage, string|int $id, string $color, ?int $closeAfterMs = null): string
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

    public function sendBrowserPush(string $topic, string $title,  string $pushMessage, string|int $id): string
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
    public function sendPlaySound(string $topic, string $soundName,  string|int $id): string
    {
        $data = [
            'type' => 'playSound',
            'soundName' => $soundName,
            'messageId' => $id,
        ];
        $update = new Update($topic, (string) json_encode($data));
        return $this->publisher->publish($update);
    }
    public function sendCleanBrowserNotification(string $topic, string|int $id): string
    {
        $data = [
            'type' => 'cleanNotification',
            'messageId' => $id,
        ];
        $update = new Update($topic, (string) json_encode($data));
        return $this->publisher->publish($update);
    }

    public function sendModal(string $topic, string $content): bool
    {

        $data = [
            'type' => 'modal',
            'content' => $content,

        ];
        $update = new Update($topic, (string) json_encode($data));
        return $this->sendUpdate($update);
    }

    public function sendRedirect(string $topic, string $url, mixed $timeout = 1000): bool
    {
        $data = [
            'type' => 'redirect',
            'url' => $url,
            'timeout' => $timeout,
        ];
        $update = new Update($topic, (string) json_encode($data));
        return $this->sendUpdate($update);
    }

    public function sendEndMeeting(string $topic, string $url, mixed $timeout = 1000): bool
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
     * @param array<string, mixed> $options
     */
    public function sendNewJitsiMeeting(string $topic, array $options): bool
    {
        $data = [
            'type' => 'newJitsi',
            'options' => $options,
        ];
        $update = new Update($topic, (string) json_encode($data));
        return $this->sendUpdate($update);
    }

    public function sendRefresh(string $topic, string $url): bool
    {
        $data = [
            'type' => 'refresh',
            'reloadUrl' => $url,
        ];
        $update = new Update($topic, (string) json_encode($data));
        return $this->sendUpdate($update);
    }

    public function sendCallAdhockmeeding(string $title, string $topic, string $message, string $pushMesage, int|string $time, string|int $id): bool
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

    public function sendRefreshDashboard(string $topic): bool
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
