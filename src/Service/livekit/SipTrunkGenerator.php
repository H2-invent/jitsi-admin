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
    private string $sipTrunkNumber;
    const  SIP_TRUNK_ID = 'sip_trunk_id';
    const SIP_DISPATCH_RULE_ID = 'sip_dispatch_rule_id';
    private Rooms $rooms;
    private Server $server;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface     $logger)
    {
    }

    public function setHttpClient(HttpClientInterface $httpClient): void
    {
        $this->httpClient = $httpClient;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function setTrunkId(string $trunkId): void
    {
        $this->trunkId = $trunkId;
    }

    public function setSipTrunkNumber(string $sipTrunkNumber): void
    {
        $this->sipTrunkNumber = $sipTrunkNumber;
    }

    public function setRooms(Rooms $rooms): void
    {
        $this->rooms = $rooms;
    }

    public function setServer(Server $server): void
    {
        $this->server = $server;
    }

    public function createNewSIPNumber(Rooms $rooms, $callerId)
    {
        try {
            $this->generateSipTrunk($rooms->getServer(), $rooms, $callerId);
            if ($this->generateDispatcherRule()) {
                return $this->sipTrunkNumber;
            }
        } catch (\Exception $exception) {
            throw new \Exception('Fehler bei der API-Anfrage: ' . $exception->getMessage());
        }
    }

    public function generateSipTrunk(Server $server, Rooms $rooms, $callerId)
    {
        $this->rooms = $rooms;
        $this->server = $server;
        $this->sipTrunkNumber = (new \DateTime())->format('U').rand(10, 99);
        $payload = [
            'trunk' => [
                'name' => $rooms->getUid(),
                'numbers' => [
                    $this->sipTrunkNumber
                ],
                'allowed_numbers' => [$callerId],
            ]
        ];

        try {
            $response = $this->sendPostRequest($server, 'twirp/livekit.SIP/CreateSIPInboundTrunk', $payload);
            if (isset($response[self::SIP_TRUNK_ID])) {
                $this->trunkId = $response[self::SIP_TRUNK_ID];
                $this->logger->debug('found ' . self::SIP_TRUNK_ID, [self::SIP_TRUNK_ID => $response[self::SIP_TRUNK_ID]]);
                return $this->trunkId;
            }
        } catch (\Exception $exception) {
            throw new \Exception('Fehler bei der API-Anfrage: ' . $exception->getMessage());

        }

    }

    public function generateDispatcherRule(): ?bool
    {
        $payload = [

            "trunk_ids" => [$this->trunkId],
            "hide_phone_number" => false,
            "rule" => [
                "dispatchRuleDirect" => [
                    "roomName" => $this->rooms->getUid(),
                    "pin" => ""
                ]
            ]
        ];
        try {
            $response = $this->sendPostRequest($this->server, 'twirp/livekit.SIP/CreateSIPDispatchRule', $payload);
            if (isset($response[self::SIP_DISPATCH_RULE_ID])) {
                $this->logger->debug('found ' . self::SIP_DISPATCH_RULE_ID, [self::SIP_DISPATCH_RULE_ID => $response[self::SIP_DISPATCH_RULE_ID]]);
                return true;
            }
        } catch (\Exception $exception) {
            throw new \Exception('Fehler bei der API-Anfrage: ' . $exception->getMessage());

        }
        return false;
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