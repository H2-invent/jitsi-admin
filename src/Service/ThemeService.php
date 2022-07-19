<?php


namespace App\Service;


use App\Entity\Rooms;
use Doctrine\ORM\EntityManagerInterface;
use H2Entwicklung\Signature\CheckSignature;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
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
    private AdapterInterface $cache;

    public function __construct(AdapterInterface $filesystemAdapter, CheckSignature $checkSignature, RequestStack $request, HttpClientInterface $httpClient, ParameterBagInterface $parameterBag, LicenseService $licenseService, LoggerInterface $logger)
    {
        $this->licenseService = $licenseService;
        $this->parameterBag = $parameterBag;
        $this->client = $httpClient;
        $this->logger = $logger;
        $this->request = $request;
        $this->checkSignature = $checkSignature;
        $this->cache = $filesystemAdapter;
    }

    public function getTheme(?Rooms $room = null)
    {
        if ($room) {
            if ($room->getHostUrl()) {
                $url = str_replace('https://', '', $room->getHostUrl());
                $url = str_replace('http://', '', $url);
            } else {
                return false;
            }
        } else {
            if ($this->request && $this->request->getCurrentRequest()) {
                $url = $this->request->getCurrentRequest()->getHost();
            } else {
                return false;
            }
        }


        try {
            $value = $this->cache->get('theme_' . $url, function (ItemInterface $item) use ($url) {
                $item->expiresAfter(3600);

                $finder = new Finder();
                $finder->files()->in($this->parameterBag->get('kernel.project_dir') . '/theme/')->name($url . '.' . 'theme.json.signed');
                if ($finder->count() > 0) {
                    $arr = iterator_to_array($finder);
                    $theme = reset($arr)->getContents();

                    $valid = $this->checkSignature->verifySignature($theme);
                    if ($valid) {
                        $res = $this->checkSignature->verifyValidUntil($theme);
                        if ($res !== false) {
                            return $res;
                        }
                        $this->logger->error('Theme valid until is before now');
                    } else {
                        $this->logger->error('Signature invalid');
                    }
                }
                return false;
            });
            return $value;

        } catch (\Exception $exception) {

        }
        return false;
    }

    public
    function getThemeProperty($property)
    {
        $theme = $this->getTheme();
        if ($theme) {
            try {
                return $theme[$property];
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    public function getApplicationProperties($input)
    {
        $variable = null;
        if ($this->parameterBag->has($input)) {
            $variable = $this->parameterBag->get($input);
        }
        $tmp = $this->getThemeProperty($input);

        if ($tmp !== null) {
            return $tmp;
        }
        return $variable;
    }
}