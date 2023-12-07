<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class JitsiComponentSelectorPublicKeyController extends AbstractController
{
    private $publicKeyPath;

    public function __construct(
        private ParameterBagInterface $parameterBag,
        private KernelInterface       $kernel,
        private LoggerInterface       $logger)
    {
        $dir = $this->kernel->getProjectDir();
        $this->publicKeyPath = $dir . $this->parameterBag->get('JITSI_COMPONENT_SELECTOR_PUBLIC_PATH');
        $this->publicKeyPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->publicKeyPath);


    }

    public function setPublicKeyPath(string $publicKeyPath): void
    {
        $this->publicKeyPath = $publicKeyPath;
    }

    #[Route('/signal/d1c8dfc1830cc0985d98acb9c6606ccb191ffdeb5c2be295c446dcea80391620.pem', name: 'app_jitsi_component_selector_public_key')]
    public function index(): Response
    {
        $publicKey = '';
        try {
            $publicKey = file_get_contents($this->publicKeyPath);
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            throw new NotFoundHttpException('This function is not activated.');
        }
        return new Response($publicKey);
    }
}
