<?php

namespace App\Tests\Livekit;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Service\livekit\SipTrunkGenerator;
use Firebase\JWT\JWT;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Psr\Log\LoggerInterface;

class SipTrunkGeneratorTest extends TestCase
{
    private $httpClient;
    private $logger;
    private $sipTrunkGenerator;
    private $rooms;
    private $server;

    protected function setUp(): void
    {
        // HttpClient und Logger mocken
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        // Instanz der SipTrunkGenerator Klasse erstellen
        $this->sipTrunkGenerator = new SipTrunkGenerator($this->httpClient, $this->logger);

        // Räume und Server mocken
        $this->rooms = $this->createMock(Rooms::class);
        $this->server = $this->createMock(Server::class);
        $this->server->method('getUrl')->willReturn('testurl.com');
        $this->server->method('getAppSecret')->willReturn('secret');
        $this->server->method('getAppId')->willReturn('key1');
        $this->rooms->method('getUid')->willReturn('test_room');
    }

    public function testCreateNewSIPNumber()
    {
        $callerId = '123456';

        // Erwartete Rückgabe von serverUrl und Serverdaten setzen
        $this->rooms->method('getServer')->willReturn($this->server);
        $this->rooms->method('getUid')->willReturn('test_room');

        // Antwort-Array für die Mock-Antwort der HTTP-Anfrage
        $responseContent = [
            SipTrunkGenerator::SIP_TRUNK_ID => 'mocked_trunk_id',
            SipTrunkGenerator::SIP_DISPATCH_RULE_ID => 'mocked_dispatch_rule_id'
        ];

        // Mock für HTTP Response einrichten
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('toArray')->willReturn($responseContent);

        $this->httpClient->method('request')->willReturn($response);

        $sipNumber = $this->sipTrunkGenerator->createNewSIPNumber($this->rooms, $callerId);

        // Überprüfen, ob die SIP-Trunk-Nummer korrekt zurückgegeben wird
        $this->assertNotNull($sipNumber);
        $this->assertIsString($sipNumber);
    }

    public function testGenerateSipTrunk()
    {
        $callerId = '123456';


        // Mock für HTTP Response einrichten
        $responseContent = [
            "sip_trunk_id" => "ST_GncVULasddsa",
            "name" => "Demo Inbound Trunk 2",
            "metadata" => "",
            "numbers" => [
                "12345678901"
            ],
            "allowed_addresses" => [],
            "allowed_numbers" => [],
            "auth_username" => "",
            "auth_password" => ""
        ];
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('toArray')->willReturn($responseContent);

        $this->httpClient->method('request')->willReturn($response);
        $this->sipTrunkGenerator->setHttpClient($this->httpClient);
        $trunkId = $this->sipTrunkGenerator->generateSipTrunk($this->server, $this->rooms, $callerId);

        // Überprüfen, ob die Trunk-ID korrekt zurückgegeben wird
        $this->assertEquals('ST_GncVULasddsa', $trunkId);
    }

    public function testGenerateDispatcherRule()
    {
        // Methode generiereSIPTrunk aufrufen, um eine trunkId zu setzen

        $responseContent = [
            "sip_dispatch_rule_id" => "SDR_qp9tqqPQTXWF",
            "rule" => [
                "dispatch_rule_direct" => [
                    "room_name" => "test2_livekit-696",
                    "pin" => ""
                ]
            ],
            "trunk_ids" => [
                "ST_xWyDaHhzXtvL"
            ],
            "hide_phone_number" => false,
            "inbound_numbers" => [],
            "name" => "",
            "metadata" => "",
            "attributes" => []
        ];
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('toArray')->willReturn($responseContent);

        $this->httpClient->method('request')->willReturn($response);
        $this->sipTrunkGenerator->setTrunkId('test_id');
        $this->sipTrunkGenerator->setRooms($this->rooms);
        $this->sipTrunkGenerator->setServer($this->server);
        $result = $this->sipTrunkGenerator->generateDispatcherRule();

        // Überprüfen, ob der Dispatcher-Rule-Erstellungsprozess erfolgreich ist
        $this->assertTrue($result);
    }

    public function testSendPostRequest()
    {
        $endpoint = 'twirp/livekit.SIP/CreateSIPInboundTrunk';
        $payload = ['key' => 'value'];

        $responseContent = ['success' => true];
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('toArray')->willReturn($responseContent);

        $this->httpClient->method('request')->willReturn($response);

        // Server URL und App Secret einrichten
        $this->server->method('getUrl')->willReturn('mocked_url.com');
        $this->server->method('getAppSecret')->willReturn('mocked_secret');
        $this->server->method('getAppId')->willReturn('mocked_id');

        $result = $this->sipTrunkGenerator->sendPostRequest($this->server, $endpoint, $payload);

        // Überprüfen, ob die Antwort als Array zurückgegeben wird
        $this->assertEquals($responseContent, $result);
    }
}
