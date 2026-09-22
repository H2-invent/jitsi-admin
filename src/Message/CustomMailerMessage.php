<?php

namespace App\Message;

use Symfony\Component\Mime\Email;

class CustomMailerMessage
{
    private string $dsn;
    private Email $email;
    private mixed $absender;
    private mixed $roomId;
    private mixed $to;

    public function __construct(string $dsn)
    {

        $this->dsn = $dsn;
    }

    public function send(Email $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getDsn(): string
    {
        return $this->dsn;
    }

    public function setDsn(string $dsn): void
    {
        $this->dsn = $dsn;
    }


    public function getEmail(): Email
    {
        return $this->email;
    }

    public function setEmail(Email $email): void
    {
        $this->email = $email;
    }

    public function getAbsender(): mixed
    {
        return $this->absender;
    }

    public function setAbsender(mixed $absender): void
    {
        $this->absender = $absender;
    }

    public function getRoomId(): mixed
    {
        return $this->roomId;
    }

    public function setRoomId(mixed $roomId): void
    {
        $this->roomId = $roomId;
    }

    public function getTo(): mixed
    {
        return $this->to;
    }

    public function setTo(mixed $to): void
    {
        $this->to = $to;
    }
}
