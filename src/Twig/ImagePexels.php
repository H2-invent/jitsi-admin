<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Checklist;
use App\Entity\MyUser;
use App\Service\MessageService;
use App\Service\PexelService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use function GuzzleHttp\Psr7\str;

class ImagePexels extends AbstractExtension
{
    private $em;
    private $pexelsService;
    public function __construct(PexelService $pexelService, EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage, EntityManagerInterface $em)
    {
        $this->em = $entityManager;
        $this->pexelsService = $pexelService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('pexelsImage', [$this, 'pexelsImage']),
        ];
    }
    public function pexelsImage()
    {

        return $this->pexelsService->getImageFromPexels();
    }
}
