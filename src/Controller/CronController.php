<?php

namespace App\Controller;

use App\Helper\JitsiAdminController;
use App\Service\ReminderService;
use App\Service\UserService;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class CronController extends JitsiAdminController
{
    /**
     * @Route("/cron/remember", name="cron_remember")
     */
    public function updateCronAkademie(Request $request, LoggerInterface $logger, UserService $userService, ReminderService $reminderService)
    {
        if ($request->get('token') !== $this->getParameter('cronToken')) {
            $message = ['error' => true, 'hinweis' => 'Token fehlerhaft', 'token' => $request->get('token'), 'ip' => $request->getClientIp()];
            $logger->error($message['hinweis'], $message);
            return new JsonResponse($message);
        }
        return new JsonResponse($reminderService->sendReminder());
    }

    /**
     * @Route("/cron/run", name="cron_run")
     */
    public function updateCronRun(Request $request, LoggerInterface $logger, KernelInterface $kernel)
    {
        if ($request->get('token') !== $this->getParameter('cronToken')) {
            $message = ['error' => true, 'hinweis' => 'Token fehlerhaft', 'token' => $request->get('token'), 'ip' => $request->getClientIp()];
            $logger->error($message['hinweis'], $message);
            return new JsonResponse($message);
        }

        try {
            $application = new Application($kernel);
            $application->setAutoExit(false);

            $input = new ArrayInput([
                'command' => 'cron:run',
                '--script-name'=>'bin/console'
            ]);

            // You can use NullOutput() if you don't need the output
            $output = new NullOutput();
            $application->run($input, $output);

        } catch (\Exception $exception) {
            return new JsonResponse(array('error' => true, 'message' => $exception->getMessage()));
        }

        return new JsonResponse(array('error' => false));
    }
}
