<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\Lobby\DirectSendService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

final class TestNotificationController extends AbstractController
{
    public function __construct(
        private DirectSendService $directSendService
    )
    {
    }

    #[Route('/room/test/notification', name: 'app_test_notification')]
    public function index(): Response
    {
        return new StreamedResponse(function () {
            set_time_limit(600);
            @ob_end_flush();
            @ob_implicit_flush(true);

            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            $topic = 'personal/' . $user->getUid();
            echo 'set user topic: ' . $topic . '<br>';
            flush();

            $this->directSendService->sendSnackbar($topic, 'test Snackbar', 'red', 5000);
            echo 'send snackbar<br>';
            flush();
            sleep(5);

            $this->directSendService->sendBrowserNotification(
                $topic,
                'test Title',
                "I'm a test message title",
                "I'm a push message text",
                '0x23',
                'red',
                5000
            );
            echo 'send browser notification<br>';
            flush();
            sleep(5);

            $this->directSendService->sendDialog($topic, 'dialog header', "I'm a test dialog body.", "do you want to accept this dialog?", [
                [
                    'class' => 'testclass',
                    'text' => 'test button text',
                    'link' => 'testlink',
                    'data' => ['roomname' => 'test'],
                ],
                [
                    'class' => 'btn btn-danger',
                    'text' => '<i class="fas fa-phone-slash"></i>',
                    'data' => [],
                ],
            ]);
            echo 'send dialog<br>';
            flush();
            sleep(5);

            $this->directSendService->sendBrowserPush($topic, 'test message for push', 'test pushmessage', '123');
            echo 'send browser push<br>';
            flush();
            sleep(5);
            $this->directSendService->sendCleanBrowserNotification($topic, 123);
            echo 'remove browser push message<br>';
            flush();
            sleep(1);
            $this->directSendService->sendMessage($topic, 'test message', 'testuser');
            echo 'send message<br>';
            flush();
            sleep(5);
            $this->directSendService->sendRefreshDashboard($topic);
            echo 'refresh dashboard<br>';
            flush();
            sleep(15);
            $this->directSendService->sendModal($topic, '<h1>test html modal</h1>');
            echo 'send modal<br>';
            flush();
            sleep(5);
            $this->directSendService->sendRedirect($topic,'room/dashboard');
            echo 'redirect to room dashboard<br>';
            flush();
            sleep(15);
            $this->directSendService->sendPlaySound($topic, 'caller', '123');
            echo 'send caller sound<br>';
            flush();
            sleep(2);
            echo 'Fertig.<br>';
            flush();
        });
    }
}
