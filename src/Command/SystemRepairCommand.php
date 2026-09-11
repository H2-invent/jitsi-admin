<?php

namespace App\Command;

use App\Controller\DashboardController;
use App\Entity\LobbyWaitungUser;
use App\Entity\Rooms;
use App\Entity\User;
use App\Service\ldap\LdapUserService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

#[\Symfony\Component\Console\Attribute\AsCommand('app:system:repair', 'Add a short description for your command')]
class SystemRepairCommand extends Command
{
    private $em;
    private SymfonyStyle $io;
    private string $logfile = 'repairLog.txt';
    private $logFileFile;

    public function __construct(
        private LdapUserService $ldapUserService,
        EntityManagerInterface $entityManager,
        private CacheItemPoolInterface $cacheItemPool,
        ?string $name = null)
    {
        parent::__construct($name);
        $this->em = $entityManager;
    }

    protected function configure(): void
    {
    }

    private const BATCH_SIZE = 500;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->io = $io;
        $io->info('We try to repair the system.....');
        $this->logFileFile = fopen($this->logfile, "a") or die("Unable to open file!");
        fwrite($this->logFileFile, sprintf(PHP_EOL . PHP_EOL . 'Repair on %s' . PHP_EOL, (new \DateTime())->format('d.m.Y H:i')));

        $count = 0;
        $io->info('--------We start with the users------');
        fwrite($this->logFileFile, sprintf('Repair emails with newline' . PHP_EOL));

        $this->repairUsers();
        $this->findDoubleEmail();
        $count += $this->repairRooms();
        $count += $this->cleanLobbyUsers();
        $count += $this->repairWaitungUser();
        $io->success(sprintf('We found %d coruppt datasets', $count));
        fclose($this->logFileFile);
        $io->info('We clear the cache');
        $this->cacheItemPool->clear();

        return Command::SUCCESS;
    }

    private function repairUsers(): void
    {
        $lastId = 0;

        while (true) {
            $users = $this->em->getRepository(User::class)
                ->createQueryBuilder('u')
                ->where('u.id > :lastId')
                ->orderBy('u.id', 'ASC')
                ->setMaxResults(self::BATCH_SIZE)
                ->setParameter('lastId', $lastId)
                ->getQuery()
                ->getResult();

            if (count($users) === 0) {
                break;
            }

            foreach ($users as $user) {
                $lastId = $user->getId();
                $this->repairEmail(user: $user);
                $this->repairUsername(user: $user);
            }

            $this->em->flush();
            $this->em->clear();
        }
    }

    private function repairRooms(): int
    {
        $count = 0;
        $lastId = 0;

        while (true) {
            $rooms = $this->em->getRepository(Rooms::class)
                ->createQueryBuilder('r')
                ->where('r.id > :lastId')
                ->orderBy('r.id', 'ASC')
                ->setMaxResults(self::BATCH_SIZE)
                ->setParameter('lastId', $lastId)
                ->getQuery()
                ->getResult();

            if (count($rooms) === 0) {
                break;
            }

            foreach ($rooms as $room) {
                $lastId = $room->getId();
                if (!$room->getModerator() || !$room->getServer()) {
                    foreach ($room->getUser() as $user) {
                        $count++;
                        $room->removeUser($user);
                    }
                    $this->em->persist($room);
                }
            }

            $this->em->flush();
            $this->em->clear();
        }

        return $count;
    }

    private function cleanLobbyUsers(): int
    {
        $count = 0;
        $cutoff = (new \DateTime())->modify('-10days');
        $lastId = 0;

        while (true) {
            $lobbyWaitingUsers = $this->em->getRepository(LobbyWaitungUser::class)
                ->createQueryBuilder('w')
                ->where('w.id > :lastId')
                ->andWhere('w.createdAt < :cutoff')
                ->orderBy('w.id', 'ASC')
                ->setMaxResults(self::BATCH_SIZE)
                ->setParameter('lastId', $lastId)
                ->setParameter('cutoff', $cutoff)
                ->getQuery()
                ->getResult();

            if (count($lobbyWaitingUsers) === 0) {
                break;
            }

            foreach ($lobbyWaitingUsers as $waitingUser) {
                $lastId = $waitingUser->getId();
                $count++;
                $this->em->remove($waitingUser);
            }

            $this->em->flush();
            $this->em->clear();
        }

        return $count;
    }

    private function repairWaitungUser()
    {
        $count = 0;
        $lastId = 0;

        while (true) {
            $waitingUsers = $this->em->getRepository(LobbyWaitungUser::class)
                ->createQueryBuilder('w')
                ->where('w.id > :lastId')
                ->orderBy('w.id', 'ASC')
                ->setMaxResults(self::BATCH_SIZE)
                ->setParameter('lastId', $lastId)
                ->getQuery()
                ->getResult();

            if (count($waitingUsers) === 0) {
                break;
            }

            foreach ($waitingUsers as $waitingUser) {
                $lastId = $waitingUser->getId();
                try {
                    $waitingUser->getCallerSession();
                } catch (\Exception $exception) {
                    $this->em->remove($waitingUser);
                    $count++;
                }
            }

            $this->em->flush();
            $this->em->clear();
        }

        return $count;
    }

    private function repairEmail(User $user)
    {
        $emailOrg = $user->getEmail();
        $email = trim($user->getEmail());

        if ($email !== $emailOrg) {
            $this->io->info(sprintf('-------%s was corrupt--------', $email));

            fwrite($this->logFileFile, sprintf('Email with newline found %s in user id %d' . PHP_EOL, $email, $user->getId()));
            $user->setEmail(email: $email);
            $this->em->persist($user);
        }
    }

    private function repairUsername(User $user)
    {
        $usernameOrg = $user->getEmail();
        $username = trim($user->getEmail());

        if ($username !== $usernameOrg) {
            $this->io->info(sprintf('-------%s was corrupt--------', $username));

            fwrite($this->logFileFile, sprintf('Email with newline found %s in user id %d' . PHP_EOL, $username, $user->getId()));
            $user->setUsername(username: $username);
            $this->em->persist($user);
        }
    }


    private function findDoubleEmail()
    {
        $checked = [];
        $count = 0;
        $countWithAccount = 0;
        $lastId = 0;

        while (true) {
            $users = $this->em->getRepository(User::class)
                ->createQueryBuilder('u')
                ->where('u.id > :lastId')
                ->orderBy('u.id', 'ASC')
                ->setMaxResults(self::BATCH_SIZE)
                ->setParameter('lastId', $lastId)
                ->getQuery()
                ->getResult();

            if (count($users) === 0) {
                break;
            }

            foreach ($users as $user) {
                $lastId = $user->getId();
                $email = $user->getEmail();
                if (!in_array($email, $checked) && $email !== 'test1@local.h2') {
                    $allEmails = $this->em->getRepository(User::class)->findBy(['email' => $email]);
                    $checked[] = $email;

                    if (sizeof($allEmails) > 1) {
                        $count++;
                        $this->io->info(sprintf('-----Double %s Email----', $email));
                        $loggedIn = null;
                        foreach ($allEmails as $d) {
                            if ($d->getKeycloakId()) {
                                $loggedIn = $d;
                            }
                        }
                        if (!$loggedIn) {
                            $loggedIn = $allEmails[0];
                        } else {
                            $countWithAccount++;
                            $this->io->info(sprintf('-----Has Account %s %s Email----', $loggedIn->getFirstName(), $loggedIn->getLastName()));
                            fwrite($this->logFileFile, sprintf('Email %s with id %d has an account and has to stay' . PHP_EOL, $loggedIn->getEmail(), $loggedIn->getId()));
                        }
                        foreach ($allEmails as $email) {
                            if ($email !== $loggedIn) {
                                fwrite($this->logFileFile, sprintf('Double email found %s in user id %d' . PHP_EOL, $email->getEmail(), $email->getId()));
                                foreach ($email->getRooms() as $room) {
                                    $loggedIn->addRoom($room);
                                    fwrite($this->logFileFile, sprintf('Add Room  with id %d from email %s to %s with id %d' . PHP_EOL, $room->getId(), $email->getEmail(), $loggedIn->getEmail(), $loggedIn->getId()));
                                }
                                foreach ($email->getAddressbookInverse() as $adressbook) {
                                    $loggedIn->addAddressbookInverse($adressbook);
                                }
                                foreach ($email->getSchedulingTimeUsers() as $schedulingTimeUser) {
                                    $loggedIn->addSchedulingTimeUser($schedulingTimeUser);
                                }
                                fwrite($this->logFileFile, sprintf('Delete User  with id %d and email %s' . PHP_EOL, $email->getId(), $email->getEmail()));
                                $this->ldapUserService->deleteUser($email);
                            }
                        }
                        $this->em->persist($loggedIn);
                    }
                }
            }

            $this->em->flush();
            $this->em->clear();
        }

        $this->io->info(sprintf('------Found %d double emails', $count));
        $this->io->info(sprintf('------Found %d account ', $countWithAccount));
    }
}
