<?php

/**
 * Created by PhpStorm.
 * User: andreas.holzmann
 * Date: 06.06.2020
 * Time: 19:01
 */

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormFactoryInterface;

class CronService
{
    private $em;
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, FormFactoryInterface $formBuilder, LoggerInterface $logger)
    {
        $this->em = $entityManager;
        $this->logger = $logger;
    }

    function check($request)
    {
        $message = false;

        if ($request->get('token') !== $this->getParameter('cronToken')) {
            $message = ['error' => true, 'hinweis' => 'Token fehlerhaft', 'token' => $request->get('token'), 'ip' => $request->getClientIp()];
            $this->logger->error($message['hinweis'], $message);
        }

        if ($this->getParameter('cronIPAdress') !== $request->getClientIp()) {
            $message = ['error' => true, 'hinweis' => 'IP Adresse fuer Cron Jobs nicht zugelassen', 'ip' => $request->getClientIp()];
            $this->logger->error($message['hinweis'], $message);
        }

        return $message;
    }
}
