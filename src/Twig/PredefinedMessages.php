<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\PredefinedLobbyMessages;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

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

    /**
     * @return PredefinedLobbyMessages[]
     */
    public function getPredefinedMessages(): array
    {

        return $this->entityManager->getRepository(PredefinedLobbyMessages::class)->findBy(['active' => true], ['priority' => 'ASC']);
    }
}
