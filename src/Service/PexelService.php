<?php


namespace App\Service;


use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PexelService
{
    private $client;
    private $parameterBag;
    public function __construct(HttpClientInterface $httpClient, ParameterBagInterface $parameterBag)
    {
        $this->client = $httpClient;
        $this->parameterBag = $parameterBag;
    }

    public function getImageFromPexels()
    {
        $image = null;
        try {
            $cache = new FilesystemAdapter();
            $value = $cache->get('pexels_image', function (ItemInterface $item)  {
                $item->expiresAfter(600);

                $s = array();
                $hour = (new \DateTime())->format('H');
                if ($hour < 7) {
                    $s = ['night', 'moon'];
                } elseif ($hour < 9) {
                    $s = ['sunrise', 'germany', 'bavaria'];
                } elseif ($hour < 11) {
                    $s = ['city', 'valley', 'animal', 'dessert', 'cat', 'forest'];
                } elseif ($hour < 17) {
                    $s = ['lake', 'ocean', 'weather', 'mountain'];
                } elseif ($hour < 23) {
                    $s = ['sunset', 'night', 'moon'];
                }

                $response = $this->client->request('GET', 'https://api.pexels.com/v1/search?query=' . $s[rand(0, sizeof($s) - 1)] . '&per_page=80', [
                        'headers' => [
                            'Authorization' => $this->parameterBag->get('laF_pexel_api_key'),
                        ]
                    ]
                );
                return $response->getContent();
            });
            $image = json_decode($value, true)['photos'][rand(0, 79)];

        }catch (\Exception $e){

        }

        return $image;
    }

}