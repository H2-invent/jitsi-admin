<?php

namespace App\Message;

use Symfony\Component\Mime\Email;

class CustomMailerMessage
{
    private string $dsn;
    private Email $email;
    /** @var mixed */
    private $absender;
    /** @var mixed */
    private $roomId;
    /** @var mixed */
    private $to;

    public function __construct(string $dsn)
    {

        $this->dsn = $dsn;
    }

    public function send(Email $email): self
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return string
     */
    public function getDsn(): string
    {
        return $this->dsn;
    }

    /**
     * @param string $dsn
     */
    public function setDsn(string $dsn): void
    {
        $this->dsn = $dsn;
    }


    /**
     * @return Email
     */
    public function getEmail(): Email
    {
        return $this->email;
    }

    /**
     * @param Email $email
     */
    public function setEmail(Email $email): void
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getAbsender(): mixed
    {
        return $this->absender;
    }

    /**
     * @param mixed $absender
     */
    public function setAbsender(mixed $absender): void
    {
        $this->absender = $absender;
    }

    /**
     * @return mixed
     */
    public function getRoomId(): mixed
    {
        return $this->roomId;
    }

    /**
     * @param mixed $roomId
     */
    public function setRoomId(mixed $roomId): void
    {
        $this->roomId = $roomId;
    }

    /**
     * @return mixed
     */
    public function getTo(): mixed
    {
        return $this->to;
    }

    /**
     * @param mixed $to
     */
    public function setTo(mixed $to): void
    {
        $this->to = $to;
    }
}
