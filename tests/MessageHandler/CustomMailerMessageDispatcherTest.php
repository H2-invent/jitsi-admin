<?php

namespace App\Tests\MessageHandler;

use App\Entity\Rooms;
use App\Message\CustomMailerMessage;
use App\MessageHandler\CustomMailerMessageDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class CustomMailerMessageDispatcherTest extends KernelTestCase
{
    private function createDispatcher(Logger $logger): CustomMailerMessageDispatcher
    {
        $container = self::getContainer();

        return new CustomMailerMessageDispatcher(
            $container->get(MailerInterface::class),
            $container->get(ParameterBagInterface::class),
            $container->get(EntityManagerInterface::class),
            $logger,
        );
    }

    private function findTestRoom(): ?Rooms
    {
        return self::getContainer()->get(EntityManagerInterface::class)
            ->getRepository(Rooms::class)
            ->findOneBy(['name' => 'TestMeeting: 1']);
    }

    public function testInvokeSendsTheEmailUsingTheGivenDsn(): void
    {
        self::bootKernel();
        $logHandler = new TestHandler();
        $dispatcher = $this->createDispatcher(new Logger('test', [$logHandler]));

        $email = (new Email())
            ->from('sender@example.com')
            ->to('receiver@example.com')
            ->subject('Subject')
            ->text('Body');

        $dispatcher((new CustomMailerMessage('null://null'))->send($email));

        $this->assertTrue($logHandler->hasDebugThatContains('null://null'));
    }

    public function testInvokeSendsNotdeliveryMailWhenTheTransportFails(): void
    {
        self::bootKernel();
        $logHandler = new TestHandler();
        $dispatcher = $this->createDispatcher(new Logger('test', [$logHandler]));
        $room = $this->findTestRoom();

        $email = (new Email())
            ->from('sender@example.com')
            ->to('receiver@example.com')
            ->subject('Subject')
            ->text('Body');

        $message = new CustomMailerMessage('smtp://127.0.0.1:1?timeout=1');
        $message->send($email);
        $message->setAbsender('receiver@example.com');
        $message->setTo('not-an-email');
        $message->setRoomId($room->getId());

        $dispatcher($message);

        $this->assertTrue($logHandler->hasErrorRecords());
        $this->assertEmailCount(1);
        $mail = $this->getMailerMessage();
        $this->assertEmailAddressContains($mail, 'to', 'receiver@example.com');
        $this->assertEmailHtmlBodyContains($mail, 'not-an-email');
        $this->assertEmailHtmlBodyContains($mail, 'TestMeeting: 1');
    }

    public function testSendNotdeliveryBuildsAnInvalidAddressNotification(): void
    {
        self::bootKernel();
        $dispatcher = $this->createDispatcher(new Logger('test'));
        $room = $this->findTestRoom();

        $method = new \ReflectionMethod($dispatcher, 'sendNotdelivery');
        $method->setAccessible(true);
        $method->invoke($dispatcher, $room, 'receiver@example.com', 'not-an-email', 'some reason');

        $this->assertEmailCount(1);
        $mail = $this->getMailerMessage();
        $this->assertEmailAddressContains($mail, 'to', 'receiver@example.com');
        $this->assertEmailHtmlBodyContains($mail, 'not-an-email');
        $this->assertEmailHtmlBodyContains($mail, 'some reason');
        $this->assertEmailHtmlBodyContains($mail, 'TestMeeting: 1');
    }
}
