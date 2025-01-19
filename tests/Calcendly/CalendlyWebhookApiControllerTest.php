<?php

namespace App\Tests\Calcendly;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\calendly\CallendlyConnect;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use function PHPUnit\Framework\assertStringContainsString;

class CalendlyWebhookApiControllerTest extends WebTestCase
{
    private $client;
    private $userRepositoryMock;
    private $callendlyConnectMock;
    private $entityManagerMock;
    private $testuser;
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->userRepositoryMock = $this->createMock(UserRepository::class);
        $this->callendlyConnectMock = $this->createMock(CallendlyConnect::class);
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $this->testuser=( self::getContainer()->get(UserRepository::class))->findOneBy(['email' => 'test@local.de']);
//        self::getContainer()->set(UserRepository::class, $this->userRepositoryMock);
        self::getContainer()->set(CallendlyConnect::class, $this->callendlyConnectMock);
//        self::getContainer()->set(EntityManagerInterface::class, $this->entityManagerMock);
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
        self::assertResponseRedirects('/room/dashboard');
    }
}