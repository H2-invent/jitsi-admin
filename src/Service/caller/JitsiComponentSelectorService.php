<?php

namespace App\Service\caller;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class JitsiComponentSelectorService
{
    private string $baseUrl;
    private string $jsonResult;

    public function __construct(
        private HttpClientInterface   $httpClient,
        private ParameterBagInterface $parameterBag)
    {
        $this->baseUrl = $this->parameterBag->get('JITSI_COMPONENT_SELECTOR_BASE_URL');
    }

    public function fetchComponentSelectorResult(
        string $baseUrl,
        string $roomName,
        string $displayName,
        ?string $jwt = null,
        bool   $autoAnswer = true,
        int    $autoAnswerTime = 1000,
        string $sipAddress = 'sip:jibri@127.0.0.1',
        string $environment = 'default-env',
        string $region = 'default-region',
        string $type = 'SIP-JIBRI'
    )
    {
        $requestData = $this->buildRequestData(
            baseUrl: $baseUrl,
            roomName: $roomName,
            displayName: $displayName,
            jwt: $jwt,
            autoAnswer: $autoAnswer,
            autoAnswerTime: $autoAnswerTime,
            sipAddress: $sipAddress,
            environment: $environment,
            region: $region,
            type: $type
        );

        $response = $this->httpClient->request(method: 'POST',url: $this->baseUrl,options: [
            'json'=>$requestData
        ]);
        if (200 != $response->getStatusCode()){
           throw new \Exception('Response status code is different than expected.');
        }
        $decodedPayload = $response->toArray();
        return $decodedPayload;
    }

    public function buildRequestData(
        string $baseUrl,
        string $roomName,
        string $displayName,
        ?string $jwt ,
        bool   $autoAnswer ,
        int    $autoAnswerTime ,
        string $sipAddress ,
        string $environment ,
        string $region ,
        string $type ,
    )
    {
        $requestData = [
            'callParams' => [
                'callUrlInfo' => [
                    'baseUrl' => $baseUrl,
                    'callName' => $roomName . ($jwt ? ('?jwt=' . $jwt) : ''),
                ],
                'componentParams' => [
                    'type' => $type,
                    'region' => $region,
                    'environment' => $environment,
                ],
                'metadata' => [
                    'sipClientParams' => [
                        'sipAddress' => $sipAddress,
                        'displayName' => $displayName,
                        'autoAnswer' => $autoAnswer,
                        'autoAnswerTimer' => $autoAnswerTime
                    ]
                ]
            ]
        ];

        return $requestData;
    }

}