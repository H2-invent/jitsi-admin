<?php


namespace App\Service;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
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

    public function getTheme(){
        if($this->parameterBag->get('enterprise_theme_url') != ''){
            $cache = new FilesystemAdapter();
            if($_ENV["APP_ENV"] === 'dev'){
                $cache->delete('theme');
            }

            $value = $cache->get('theme', function (ItemInterface $item) {
                $item->expiresAfter(21600);
                $response = $this->client->request('GET', $this->parameterBag->get('enterprise_theme_url'))->getContent();
                $valid = $this->licenseService->verifySignature($response);
                if($valid) {

                    $entry = json_decode($response, true);
                    if(new \DateTime($entry['validUntil']) > new \DateTime()){
                        return $entry['entry'];
                    }else{
                        return false;
                    }

                }else{
                    return false;
                }
            });

            return $value;
        }
        return false;
    }

}