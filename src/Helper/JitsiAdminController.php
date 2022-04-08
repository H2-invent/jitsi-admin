<?php

namespace App\Helper;


use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class JitsiAdminController extends AbstractController
{
    protected $doctrine;
    protected $translator;
    protected $logger;
    protected $parameterBag;

    public function __construct(ManagerRegistry $managerRegistry, TranslatorInterface $translator, LoggerInterface $logger, ParameterBagInterface $parameterBag)
    {
        $this->doctrine = $managerRegistry;
        $this->translator = $translator;
        $this->logger = $logger;
        $this->parameterBag = $parameterBag;
    }
}