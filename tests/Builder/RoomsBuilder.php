<?php

declare(strict_types=1);

namespace App\Tests\Builder;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class RoomsBuilder
{
    private Rooms $room;

    private function __construct(Server $server)
    {
        $suffix = self::uniqueSuffix();
        $start = new \DateTimeImmutable('now');

        $this->room = (new Rooms())
            ->setServer($server)
            ->setName("Room {$suffix}")
            ->setUid("uid_{$suffix}")
            ->setUidReal("uid_real_{$suffix}")
            ->setTimeZone('Europe/Berlin')
            ->setAgenda("Test agenda {$suffix}")
            ->setDuration(60)
            ->setSequence(0)
            ->setDissallowPrivateMessage(false)
            ->setDissallowScreenshareGlobal(false)
            ->setSlug("room-{$suffix}")
            ->setScheduleMeeting(false)
            ->setHostUrl('http://localhost:8000')
            ->setStart($start)
            ->setEnddate($start->modify('+1 hour'));
    }

    public static function create(Server $server): self
    {
        return new self($server);
    }

    public function withModerator(?User $moderator): self
    {
        $this->room->setModerator($moderator);

        return $this;
    }

    public function withCreator(?User $creator): self
    {
        $this->room->setCreator($creator);

        return $this;
    }

    public function withParticipant(User $participant): self
    {
        $this->room->addUser($participant);

        return $this;
    }

    public function withUidReal(string $uidReal): self
    {
        $this->room->setUidReal($uidReal);

        return $this;
    }

    public function withUid(string $uid): self
    {
        $this->room->setUid($uid);

        return $this;
    }

    public function withName(string $name): self
    {
        $this->room->setName($name);

        return $this;
    }

    public function withTimeZone(?string $timeZone): self
    {
        $this->room->setTimeZone($timeZone);

        return $this;
    }

    public function withAgenda(?string $agenda): self
    {
        $this->room->setAgenda($agenda);

        return $this;
    }

    public function withDuration(float $duration): self
    {
        $this->room->setDuration($duration);

        return $this;
    }

    public function withSequence(int $sequence): self
    {
        $this->room->setSequence($sequence);

        return $this;
    }

    public function withDissallowPrivateMessage(?bool $dissallowPrivateMessage): self
    {
        $this->room->setDissallowPrivateMessage($dissallowPrivateMessage);

        return $this;
    }

    public function withDissallowScreenshareGlobal(?bool $dissallowScreenshareGlobal): self
    {
        $this->room->setDissallowScreenshareGlobal($dissallowScreenshareGlobal);

        return $this;
    }

    public function withSlug(?string $slug): self
    {
        $this->room->setSlug($slug);

        return $this;
    }

    public function withScheduleMeeting(?bool $scheduleMeeting): self
    {
        $this->room->setScheduleMeeting($scheduleMeeting);

        return $this;
    }

    public function withHostUrl(?string $hostUrl): self
    {
        $this->room->setHostUrl($hostUrl);

        return $this;
    }

    public function withStart(?\DateTimeInterface $start): self
    {
        $this->room->setStart($start);

        return $this;
    }

    public function withEnddate(?\DateTimeInterface $enddate): self
    {
        $this->room->setEnddate($enddate);

        return $this;
    }

    public function build(): Rooms
    {
        return $this->room;
    }

    public function persist(EntityManagerInterface $entityManager, bool $flush = true): Rooms
    {
        $entityManager->persist($this->room);
        if ($flush) {
            $entityManager->flush();
        }

        return $this->room;
    }

    private static function uniqueSuffix(): string
    {
        return bin2hex(random_bytes(8));
    }
}



