<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Checklist;
use App\Entity\MyUser;
use App\Entity\PredefinedLobbyMessages;
use App\Service\MessageService;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use function GuzzleHttp\Psr7\str;

class PredefinedMessages extends AbstractExtension
{
    public function __construct(
        private EntityManagerInterface $entityManager
    )
    {
    }

    public function getFunctions(): array
    {

        return [
            new TwigFunction('getPredefinedMessages', [$this, 'getPredefinedMessages']),

        ];
    }

    public function getPredefinedMessages()
    {

        return $this->entityManager->getRepository(PredefinedLobbyMessages::class)->findBy(['active' => true], ['priority' => 'ASC']);
    }
}
