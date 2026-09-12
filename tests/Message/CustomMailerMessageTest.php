<?php

namespace App\Tests\Message;

use App\Message\CustomMailerMessage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Email;

class CustomMailerMessageTest extends TestCase
{
    public function testConstructorExposesDsn(): void
    {
        $message = new CustomMailerMessage('smtp://user:pass@host:25');

        $this->assertSame('smtp://user:pass@host:25', $message->getDsn());
    }

    public function testSendStoresEmailAndReturnsSelf(): void
    {
        $message = new CustomMailerMessage('null://null');
        $email = (new Email())->from('sender@example.com')->to('receiver@example.com');

        $this->assertSame($message, $message->send($email));
        $this->assertSame($email, $message->getEmail());
    }

    public function testSetDsn(): void
    {
        $message = new CustomMailerMessage('first');
        $message->setDsn('second');

        $this->assertSame('second', $message->getDsn());
    }

    public function testSetAndGetEmail(): void
    {
        $message = new CustomMailerMessage('null://null');
        $email = new Email();
        $message->setEmail($email);

        $this->assertSame($email, $message->getEmail());
    }

    public function testSetAndGetAbsender(): void
    {
        $message = new CustomMailerMessage('null://null');
        $message->setAbsender('sender@example.com');

        $this->assertSame('sender@example.com', $message->getAbsender());
    }

    public function testSetAndGetRoomId(): void
    {
        $message = new CustomMailerMessage('null://null');
        $message->setRoomId(4711);

        $this->assertSame(4711, $message->getRoomId());
    }

    public function testSetAndGetTo(): void
    {
        $message = new CustomMailerMessage('null://null');
        $message->setTo('receiver@example.com');

        $this->assertSame('receiver@example.com', $message->getTo());
    }
}
