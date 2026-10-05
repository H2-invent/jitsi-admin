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
    private string $publicKeyPath;

    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly KernelInterface       $kernel,
        private readonly LoggerInterface       $logger)
    {
        $dir = $this->kernel->getProjectDir();
        /** @var string $publicPath */
        $publicPath = $this->parameterBag->get('JITSI_COMPONENT_SELECTOR_PUBLIC_PATH');
        $this->publicKeyPath = $dir . $publicPath;
        $this->publicKeyPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->publicKeyPath);


    }

    public function setPublicKeyPath(string $publicKeyPath): void
    {
        $this->publicKeyPath = $publicKeyPath;
    }

    #[\Symfony\Component\Routing\Attribute\Route('/signal/{keyfile}', name: 'app_jitsi_component_selector_public_key')]
    public function index(string $keyfile): Response
    {
        if (!str_ends_with($keyfile, '.pem')) {
            throw new NotFoundHttpException('File Not Found');
        }

        $publicKey = @file_get_contents($this->publicKeyPath.$keyfile);
        if ($publicKey === false) {
            $msg = 'This function is not activated.';
            $this->logger->error($msg);
            throw new NotFoundHttpException($msg);
        }

        return new Response($publicKey);
    }
}
