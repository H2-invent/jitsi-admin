<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\PredefinedLobbyMessages;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PredefinedMessages
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    )
    {
    }

    /**
     * @return PredefinedLobbyMessages[]
     */
    #[\Twig\Attribute\AsTwigFunction(name: 'getPredefinedMessages')]
    public function getPredefinedMessages(): array
    {

        return $this->entityManager->getRepository(PredefinedLobbyMessages::class)->findBy(['active' => true], ['priority' => 'ASC']);
    }
}
