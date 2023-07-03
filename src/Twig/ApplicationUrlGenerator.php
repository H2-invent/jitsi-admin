<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Checklist;
use App\Entity\LobbyWaitungUser;
use App\Entity\MyUser;
use App\Entity\Rooms;
use App\Entity\User;
use App\Helper\ExternalApplication;
use App\Service\MessageService;
use App\Service\ParticipantSearchService;
use Psr\Log\LoggerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use function GuzzleHttp\Psr7\str;

class ApplicationUrlGenerator extends AbstractExtension
{
    public function __construct(
        private ExternalApplication      $externalApplication,
        private ParticipantSearchService $participantSearchService,
        private LoggerInterface          $logger,
    )
    {
    }

    public function getFunctions(): array
    {

        return [
            new TwigFunction('createWhitebophirLink', [$this, 'createWhitebophirLink']),
            new TwigFunction('createEtherpadLink', [$this, 'createEtherpadLink']),

        ];
    }


    public function createEtherpadLink(Rooms $rooms, User|LobbyWaitungUser|null $user = null)
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

    public function createWhitebophirLink(Rooms $rooms, $moderator = false)
    {

        return $this->externalApplication->whitebophirLink($rooms, $moderator);
    }
}
