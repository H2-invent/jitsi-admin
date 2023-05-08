<?php

namespace App\Tests\Mailer;

use App\Entity\LdapUserProperties;
use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\User;
use App\Service\MailerService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MailerServiceTest extends KernelTestCase
{
    private $userReciever;
    private $userSender;
    private $server;
    private $room;


    private function prepare()
    {
        $this->server = new Server();
        $this->server
            ->setUrl('meet.jit.si')
            ->setFeatureEnableByJWT(false)
            ->setSmtpHost(null)
            ->setSmtpEncryption('ssl')
            ->setSmtpPassword('test')
            ->setSmtpPort(564)
            ->setSmtpSenderName('mein Sender')
            ->setSmtpUsername('username')
            ->setSmtpEmail('local@local.de');
        $this->userSender = new User();
        $this->userSender->setCreatedAt(new \DateTime())
            ->setEmail('test@test.de')
            ->setFirstName('testVorname')
            ->setLastName('testLastName')
            ->setTimeZone('Europe/Berlin')
            ->setKeycloakId('testId')
            ->setLastLogin(new \DateTime())
            ->setUsername('test@test.de');
        $this->userReciever = new User();
        $this->userReciever->setCreatedAt(new \DateTime())
            ->setEmail('test2@test.de')
            ->setFirstName('testVorname2')
            ->setLastName('testLastName2')
            ->setTimeZone('Europe/Berlin')
            ->setKeycloakId('testId2')
            ->setLastLogin(new \DateTime())
            ->setUsername('test2@test.de');
        $this->room = new Rooms();
        $this->room->setModerator($this->userSender)
            ->addUser($this->userSender)
            ->addUser($this->userReciever)
            ->setName('Test Konferenz')
            ->setAgenda('testagenda')
            ->setTimeZone('Europe/Berlin')
            ->setServer($this->server)
            ->setStart(new \DateTime())
            ->setDuration(60)
            ->setEnddate((new \DateTime())->modify('+60min'));
    }

    public function testCreateMailer(): void
    {
        $this->prepare();
        $kernel = self::bootKernel();
        //$routerService = self::getContainer()->get('router');
        $mailerService = self::getContainer()->get(MailerService::class);

        $mailer = $mailerService->buildTransport($this->server);
        $this->assertFalse($mailer);
        $this->server->setSmtpHost('localhost');
        $mailer = $mailerService->buildTransport($this->server);
        $this->assertNotFalse($mailer);
    }

    public function testSendEmailSenderHasEmail(): void
    {
        $kernel = self::bootKernel();
        //$routerService = self::getContainer()->get('router');
        $mailerService = self::getContainer()->get(MailerService::class);
        $this->prepare();
        $res = $mailerService->sendEmail($this->userReciever, 'testEmail', 'TestEmailContent', $this->server, $this->userSender->getEmail(), $this->room);
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        $this->assertEmailHtmlBodyContains($email, 'TestEmailContent');
        self::assertEmailAddressContains($email, 'from', 'register@local.local');
    }
    public function testSendEmailSenderHasEmailNoRoom(): void
    {
        $kernel = self::bootKernel();
        //$routerService = self::getContainer()->get('router');
        $mailerService = self::getContainer()->get(MailerService::class);
        $this->prepare();
        $res = $mailerService->sendEmail($this->userReciever, 'testEmail', 'TestEmailContent', $this->server, $this->userSender->getEmail());
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        $this->assertEmailHtmlBodyContains($email, 'TestEmailContent');
        self::assertEmailAddressContains($email, 'from', 'register@local.local');
        self::assertEmailAddressContains($email, 'reply-to', 'test@test.de');
    }
    public function testSendEmailSenderHasEmailNoRoomNoReply(): void
    {
        $kernel = self::bootKernel();
        //$routerService = self::getContainer()->get('router');
        $mailerService = self::getContainer()->get(MailerService::class);
        $this->prepare();
        $res = $mailerService->sendEmail($this->userReciever, 'testEmail', 'TestEmailContent', $this->server);
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        $this->assertEmailHtmlBodyContains($email, 'TestEmailContent');
        self::assertEmailAddressContains($email, 'from', 'register@local.local');
    }
    public function testSendEmailSenderNoEmail(): void
    {
        $kernel = self::bootKernel();
        //$routerService = self::getContainer()->get('router');
        $mailerService = self::getContainer()->get(MailerService::class);
        $this->prepare();
        $this->userSender->setEmail('testUser');
        $res = $mailerService->sendEmail($this->userReciever, 'testEmail', 'TestEmailContent', $this->server, $this->userSender->getEmail(), $this->room);
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        $this->assertEmailHtmlBodyContains($email, 'TestEmailContent');
        self::assertEmailAddressContains($email, 'from', 'register@local.local');
    }

    public function testSendEmailRecieverNoEmailnoLDAP(): void
    {
        $kernel = self::bootKernel();
        //$routerService = self::getContainer()->get('router');
        $mailerService = self::getContainer()->get(MailerService::class);
        $this->prepare();
        $this->userReciever->setEmail('testUser');
        $res = $mailerService->sendEmail($this->userReciever, 'testEmail', 'TestEmailContent', $this->server, $this->userSender->getEmail(), $this->room);
        $this->assertEmailCount(0);
    }
    public function testSendEmailRecieverNoEmailhasLDAP(): void
    {
        $kernel = self::bootKernel();
        //$routerService = self::getContainer()->get('router');
        $mailerService = self::getContainer()->get(MailerService::class);
        $this->prepare();
        $this->userReciever->setEmail('testUser');
        $ldap = new LdapUserProperties();
        $ldap->setLdapDn('test')
            ->setLdapHost('localhost')
            ->setRdn('test')
            ->setUser($this->userReciever);
        $this->userReciever->setLdapUserProperties($ldap);
        $res = $mailerService->sendEmail($this->userReciever, 'testEmail', 'TestEmailContent', $this->server, $this->userSender->getEmail(), $this->room);
        $this->assertTrue($res);
    }
    public function testSendEmailRecieverHasEmailhasLDAP(): void
    {
        $kernel = self::bootKernel();
        //$routerService = self::getContainer()->get('router');
        $mailerService = self::getContainer()->get(MailerService::class);
        $this->prepare();
        $ldap = new LdapUserProperties();
        $ldap->setLdapDn('test')
            ->setLdapHost('localhost')
            ->setRdn('test')
            ->setUser($this->userReciever);
        $this->userReciever->setLdapUserProperties($ldap);
        $res = $mailerService->sendEmail($this->userReciever, 'testEmail', 'TestEmailContent', $this->server, $this->userSender->getEmail(), $this->room);
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        $this->assertEmailHtmlBodyContains($email, 'TestEmailContent');
        self::assertEmailAddressContains($email, 'from', 'register@local.local');
    }
    public function testSendEmailSenderhasCC(): void
    {
        $kernel = self::bootKernel();
        //$routerService = self::getContainer()->get('router');
        $mailerService = self::getContainer()->get(MailerService::class);
        $this->prepare();
        $this->userReciever->setSecondEmail('testUser@local.de,test2@local.de');
        $res = $mailerService->sendEmail($this->userReciever, 'testEmail', 'TestEmailContent', $this->server, $this->userSender->getEmail(), $this->room);
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        $this->assertEmailHtmlBodyContains($email, 'TestEmailContent');
        self::assertEmailAddressContains($email, 'cc', 'test2@local.de');
        self::assertEmailAddressContains($email, 'cc', 'testUser@local.de');
    }
}
