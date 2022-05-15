<?php


namespace App\Service;


use Doctrine\ORM\EntityManagerInterface;
use H2Entwicklung\Signature\CheckSignature;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ThemeService
{
    private $licenseService;
    private $parameterBag;
    private $client;
    private $logger;
    private RequestStack $request;
    private CheckSignature $checkSignature;
    public function __construct(CheckSignature $checkSignature, RequestStack $request, HttpClientInterface $httpClient, ParameterBagInterface $parameterBag, LicenseService $licenseService, LoggerInterface $logger)
    {
        $this->licenseService = $licenseService;
        $this->parameterBag = $parameterBag;
        $this->client = $httpClient;
        $this->logger = $logger;
        $this->request = $request;
        $this->checkSignature = $checkSignature;
    }

    public function getTheme()
    {
        if (!$this->request->getCurrentRequest()){
            return false;
        }
        $url = $this->request->getCurrentRequest()->getHost();
        try {

            $finder = new Finder();
            $finder->files()->in($this->parameterBag->get('kernel.project_dir') . '/theme/')->name($url.'.'.'theme.json.signed');
            if ($finder->count() > 0) {
                $arr = iterator_to_array($finder);
                $theme = reset($arr)->getContents();
                $valid = $this->checkSignature->verifySignature($theme);
                if ($valid){
                    $res = $this->checkSignature->verifyValidUntil($theme);
                    if ($res !== false) {
                        return $res;
                    }
                    $this->logger->error('Theme valid until is bevore now');
                }else{
                    $this->logger->error('Signature invalid');
                }
            }
        } catch (\Exception $exception) {

        }
        return false;
    }

    public function getThemeProperty($property)
    {
        $theme = $this->getTheme();
        if ($theme) {
            try {
                return $theme[$property];
            } catch (\Exception $e) {
                return false;
            }
        }
        return false;
    }
}