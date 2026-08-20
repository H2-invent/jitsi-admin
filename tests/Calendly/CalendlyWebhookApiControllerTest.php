<?php

namespace App\Tests\Calendly;

use App\Repository\UserRepository;
use App\Service\calendly\CallendlyConnect;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use function PHPUnit\Framework\assertStringContainsString;

#[AllowMockObjectsWithoutExpectations]
class CalendlyWebhookApiControllerTest extends WebTestCase
{
    private $client;
    private $callendlyConnectMock;
    private $testuser;
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->callendlyConnectMock = $this->createStub(CallendlyConnect::class);
        $this->testuser=( self::getContainer()->get(UserRepository::class))->findOneBy(['email' => 'test@local.de']);
        self::getContainer()->set(CallendlyConnect::class, $this->callendlyConnectMock);
    }

    public function testConnectWithValidToken(): void
    {

        $this->client->loginUser($this->testuser);

        $this->callendlyConnectMock
            ->method('getUserInfo')
            ->willReturn([
                'resource' => [
                    'uri' => 'calendly_user_uri',
                    'current_organization' => 'calendly_org_uri'
                ]
            ]);

//        $this->userRepositoryMock
//            ->method('findOneBy')
//            ->with(['calendly_user_uri' => 'calendly_user_uri'])
//            ->willReturn(null);

        $this->client->loginUser($this->testuser);
        $this->client->request('GET', '/room/calendly/connect');
        self::assertResponseIsSuccessful();
        assertStringContainsString('Calendly Verknüpfung',$this->client->getResponse()->getContent());
        $this->assertNull($this->testuser->getCalendlyUserUri());
        $this->assertNull($this->testuser->isCalendlySucessfullyAdded());
        $this->client->request('POST', '/room/calendly/connect', [
            'calendly_token' => 'valid-token'
        ]);
//        self::assertResponseRedirects('/room/dashboard');
    }
}