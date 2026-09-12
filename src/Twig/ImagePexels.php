<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Service\PexelService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ImagePexels extends AbstractExtension
{
    private $em;
    private $pexelsService;
    public function __construct(PexelService $pexelService, EntityManagerInterface $entityManager)
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
