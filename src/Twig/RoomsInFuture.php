<?php

// src/Twig/AppExtension.php
namespace App\Twig;

use App\Entity\Rooms;
use App\Entity\Server;
use App\Service\LicenseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class RoomsInFuture extends AbstractExtension
{
    private $licenseService;
    private $em;

    public function __construct(EntityManagerInterface $entityManager, LicenseService $licenseService)
    {
        $this->licenseService = $licenseService;
        $this->em = $entityManager;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('roomsinFuture', [$this, 'roomsinFuture']),
        ];
    }

    public function roomsinFuture(Server $server)
    {
        $now = new \DateTime('now', new \DateTimeZone('utc'));
        $qb = $this->em->getRepository(Rooms::class)->createQueryBuilder('rooms');
        $qb->andWhere('rooms.server = :server')
            ->andWhere('rooms.showRoomOnJoinpage = true')
            ->leftJoin('rooms.repeaterProtoype', 'repeaterProtoype')
            ->andWhere($qb->expr()->isNull('repeaterProtoype.id'))
            ->leftJoin('rooms.repeater', 'repeater')
            ->andWhere($qb->expr()->isNull('repeater.id'))
            ->andWhere('rooms.startUtc > :now')
            ->andWhere($qb->expr()->isNotNull('rooms.moderator'))
            ->setParameter('server', $server)
            ->setParameter('now', $now)
            ->orderBy('rooms.startUtc', 'ASC');
        $rooms = $qb->getQuery()->getResult();

        return $rooms;
    }
}
