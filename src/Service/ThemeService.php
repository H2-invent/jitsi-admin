<?php


namespace App\Service;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ThemeService
{
    private $licenseService;
    private $parameterBag;
    private $client;

    public function __construct(HttpClientInterface $httpClient, ParameterBagInterface $parameterBag, LicenseService $licenseService, TokenStorageInterface $tokenStorage, EntityManagerInterface $em)
    {
        $this->licenseService = $licenseService;
        $this->parameterBag = $parameterBag;
        $this->client = $httpClient;
    }

    public function getTheme()
    {
        try {
            $finder = new Finder();
            $finder->files()->in($this->parameterBag->get('kernel.project_dir') . '/theme/')->name('theme.json.signed');
            if ($finder->count() > 0) {
                $arr = iterator_to_array($finder);
                $theme = reset($arr)->getContents();
                $valid = $this->licenseService->verifySignature($theme);
                if ($valid){
                    $res = $this->licenseService->verifyValidUntil($theme);
                    if ($res !== false) {
                        return $res;
                    }
                }
            }
        } catch (\Exception $exception) {

        }


        if ($this->parameterBag->get('enterprise_theme_url') != '') {
            $cache = new FilesystemAdapter();
            if ($_ENV["APP_ENV"] === 'dev') {
                $cache->delete('theme');
            }

            $value = $cache->get('theme', function (ItemInterface $item) {
                $item->expiresAfter(21600);


                $response = $this->client->request('GET', $this->parameterBag->get('enterprise_theme_url'))->getContent();
                $valid = $this->licenseService->verifySignature($response);
                if ($valid) {
                    return $this->licenseService->verifyValidUntil($response);
                } else {
                    return false;
                }
            });
            return $value;
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