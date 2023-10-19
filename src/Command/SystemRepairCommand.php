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

class SystemRepairCommand extends Command
{
    protected static $defaultName = 'app:system:repair';
    protected static $defaultDescription = 'Add a short description for your command';
    private $em;
    private SymfonyStyle $io;
    private string $logfile = 'repairLog.txt';
    private $logFileFile;

    public function __construct(
        private LdapUserService $ldapUserService,
        EntityManagerInterface $entityManager,
        private CacheItemPoolInterface $cacheItemPool,
        string $name = null)
    {
        parent::__construct($name);
        $this->em = $entityManager;
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->io = $io;
        $io->info('We try to repair the system.....');
        $this->logFileFile = fopen($this->logfile, "a") or die("Unable to open file!");
        fwrite($this->logFileFile, sprintf(PHP_EOL . PHP_EOL . 'Repair on %s' . PHP_EOL, (new \DateTime())->format('d.m.Y H:i')));
        $count = 0;
        $user = $this->em->getRepository(User::class)->findAll();
        $io->info('--------We start with the users------');
        fwrite($this->logFileFile, sprintf('Repair emails with newline' . PHP_EOL));

        foreach ($user as $u) {
            $this->repairEmail(user: $u);
            $this->repairUsername(user: $u);
        }
        $this->em->flush();
        $this->findDoubleEmail();
        $rooms = $this->em->getRepository(Rooms::class)->findAll();

        foreach ($rooms as $room) {
            if (!$room->getModerator() || !$room->getServer()) {
                foreach ($room->getUser() as $user) {
                    $count++;
                    $room->removeUser($user);
                }
                $this->em->persist($room);
            }
        }
        $this->em->flush();
        $lobbyWaitingUser = $this->em->getRepository(LobbyWaitungUser::class)->findAll();
        foreach ($lobbyWaitingUser as $waitingUser) {
            if ($waitingUser->getCreatedAt() < (new \DateTime())->modify('-10days')) {
                $count++;
                $this->em->remove($waitingUser);
            }
        }
        $this->em->flush();
        $user = $this->em->getRepository(User::class)->findAll();
        $count += $this->repairWaitungUser();
        $io->success(sprintf('We found %d coruppt datasets', $count));
        fclose($this->logFileFile);
        $io->info('We clear the cache');
        $this->cacheItemPool->clear();
        return Command::SUCCESS;
    }

    private function repairWaitungUser()
    {
        $waitingUser = $this->em->getRepository(LobbyWaitungUser::class)->findAll();
        $count = 0;
        foreach ($waitingUser as $data) {
            try {
                $session = $data->getCallerSession();
            } catch (\Exception $exception) {
                $this->em->remove($data);
                $count++;
            }
        }
        $this->em->flush();
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
        $user = $this->em->getRepository(User::class)->findAll();
        $checked = [];
        $count = 0;
        $countWithAccount = 0;
        foreach ($user as $u) {
            $email = $u->getEmail();
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
        $this->io->info(sprintf('------Found %d double emails', $count));
        $this->io->info(sprintf('------Found %d account ', $countWithAccount));
    }
}
