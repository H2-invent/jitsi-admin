<?php

namespace App\Service\livekit;

use App\Entity\Rooms;
use App\Entity\Server;
use Firebase\JWT\JWT;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SipTrunkGenerator
{
    private string $trunkId;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface     $logger)
    {
    }

    public function generateSipTrunk(Server $server, Rooms $rooms)
    {
        $sipTrunkNumber = rand(10000000000000, 999999999999);
        $payload = [
            'trunk' => [
                'name' => $rooms->getUid(),
                'numbers' => [
                    $sipTrunkNumber
                ]
            ]
        ];

        $response = $this->sendPostRequest($server, 'twirp/livekit.SIP/CreateSIPInboundTrunk', $payload);
        if (isset($response['trunk_id'])) {
        $this->logger->debug('found trunkkId',['truk id'=>$response['trunk_id']]);
        }
    }

    public function generateDispatcherRule(Server $server, $trunkId)
    {

    }

    /**
     * Führt einen GET-Request gegen die API der Server-URL aus.
     *
     * @param Server $server
     * @param string $endpoint Der spezifische Endpunkt, der angesprochen werden soll.
     * @param array $payload Zusätzliche Parameter, die an die Anfrage angehängt werden.
     * @return array|null Gibt die Antwortdaten als Array zurück oder null bei Fehlern.
     * @throws \Exception
     */
    public function sendPostRequest(Server $server, string $endpoint, array $payload = []): ?array
    {
        // Baue die vollständige URL
        $url = 'https://' . $server->getUrl() . '/' . ltrim($endpoint, '/');

        // Setze Header und Query-Parameter
        $headers = [
            'Authorization' => 'Bearer ' . $this->generateJwtToken($server),
            'Content-Type' => 'application/json',
        ];

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => $headers,
                'json' => $payload,  // Nutzdaten als JSON
            ]);

            // Überprüfe den Statuscode der Antwort
            if ($response->getStatusCode() !== 200) {
                throw new \Exception('API-Anfrage fehlgeschlagen: ' . $response->getStatusCode());
            }

            // Antwortinhalt dekodieren und zurückgeben
            return $response->toArray();

        } catch (\Exception $e) {
            // Fehlerbehandlung
            // Hier könnten Logs geschrieben oder andere Fehlerbehandlungen vorgenommen werden.
            throw new \Exception('Fehler bei der API-Anfrage: ' . $e->getMessage());
        }
    }

    private function generateJwtToken(Server $server): string
    {
        $key = $server->getAppSecret();
        $issuedAt = time();
        $expire = $issuedAt + 3600;  // Token läuft in 1 Stunde ab
        $notBefore = $issuedAt;  // Token kann sofort verwendet werden

        $payload = [
            'iss' => $server->getAppId(),   // Aussteller
            'exp' => $expire,               // Ablaufzeit
            'nbf' => $notBefore,            // Gültig ab
            'sip' => [
                'admin' => true
            ]
        ];

        return JWT::encode($payload, $key, 'HS256');
    }
}