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
        if ($this->parameterBag->get('laF_pexel_api_key') !== '' && $this->parameterBag->get('enterprise_noExternal') == 0) {
            try {
                $cache = new FilesystemAdapter();

                $value = $cache->get(
                    'pexels_image',
                    function (ItemInterface $item) {
                        $item->expiresAfter(intval($this->parameterBag->get('laF_pexel_refresh_time')));

                        $s = [];
                        $hour = (new \DateTime())->format('H');
                        if ($hour < 7) {
                            $s = ['night', 'northern lights'];
                        } elseif ($hour < 9) {
                            $s = ['sunrise', 'germany', 'bavaria'];
                        } elseif ($hour < 11) {
                            $s = ['city', 'valley', 'animal', 'dessert', 'cat', 'forest', 'mountain'];
                        } elseif ($hour < 18) {
                            $s = ['lake', 'ocean', 'underwater', 'reef'];
                        } elseif ($hour < 21) {
                            $s = ['sunset', 'clouds'];
                        } else {
                            $s = ['night', 'northern lights'];
                        }

                        $response = $this->client->request(
                            'GET',
                            'https://api.pexels.com/v1/search?query=' . $s[rand(0, sizeof($s) - 1)] . '&per_page=80',
                            [
                                'headers' => [
                                    'Authorization' => $this->parameterBag->get('laF_pexel_api_key'),
                                ]
                            ]
                        );
                        return $response->getContent();
                    }
                );
                $imageArr = json_decode($value, true)['photos'];
                $image = $imageArr[rand(0, sizeof($imageArr) - 1)];
            } catch (\Exception $e) {
            }
        }
        return $image;
    }
}
