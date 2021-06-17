<?php
// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Checklist;
use App\Entity\MyUser;
use App\Entity\Server;
use App\Service\LicenseService;
use App\Service\MessageService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use function GuzzleHttp\Psr7\str;

class Theme extends AbstractExtension
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

    public function getFunctions(): array
    {

        return [
            new TwigFunction('getThemeProperties', [$this, 'getThemeProperties']),
        ];
    }

    public function getThemeProperties()
    {
        if($this->parameterBag->get('enterprise_theme_url') != ''){
            $response = $this->client->request('GET', $this->parameterBag->get('enterprise_theme_url'))->getContent();
            $valid = $this->licenseService->verifySignature($response);
            if($valid){
                $entry = json_decode($response,true);
                return $entry['entry'];
            }else{
                return false;
            }
        }
        return false;


    }

}