<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\LobbyWaitungUser;
use App\Entity\Rooms;
use App\Entity\User;
use App\Helper\ExternalApplication;
use App\Service\ParticipantSearchService;
use Psr\Log\LoggerInterface;

class ApplicationUrlGenerator
{
    public function __construct(
        private readonly ExternalApplication      $externalApplication,
        private readonly ParticipantSearchService $participantSearchService,
        private readonly LoggerInterface          $logger,
    ) {
    }


    #[\Twig\Attribute\AsTwigFunction(name: 'createEtherpadLink')]
    public function createEtherpadLink(Rooms $rooms, User|LobbyWaitungUser|null $user = null): string
    {
        try {
            $name = null;
            if ($user instanceof User) {
                $name = $this->participantSearchService->buildShowInFrontendStringNoString($user);
            } elseif ($user instanceof LobbyWaitungUser) {
                $name = $user->getShowName();
            }
            return $this->externalApplication->etherpadLink($rooms, $name);
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            return $this->externalApplication->etherpadLink($rooms);
        }
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'createWhitebophirLink')]
    public function createWhitebophirLink(Rooms $rooms, bool $moderator = false): string
    {
        return $this->externalApplication->whitebophirLink($rooms, $moderator);
    }
}
