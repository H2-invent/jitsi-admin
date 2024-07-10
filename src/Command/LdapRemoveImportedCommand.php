<?php

namespace App\Command;

use App\Entity\User;
use App\Service\ldap\LdapService;
use App\Service\ldap\LdapUserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[\Symfony\Component\Console\Attribute\AsCommand('app:ldap:removeServer', 'This command removes the Users from the selected LDAP. This Command also removes the users  from the global adressbook and removes all created conferences of the users. The users are not able to login after this action')]
class LdapRemoveImportedCommand extends Command
{
    private $paramterBag;
    private $ldapService;
    private $ldapUserService;
    private $USERDN;
    private $LDAPSERVERID;
    private $URL;
    private $em;
    public function __construct(LdapUserService $ldapUserService, ParameterBagInterface $parameterBag, LdapService $ldapService, EntityManagerInterface $entityManager, string $name = null)
    {
        parent::__construct($name);
        $this->paramterBag = $parameterBag;
        $this->ldapService = $ldapService;
        $this->ldapUserService = $ldapUserService;
        $this->LDAPSERVERID = explode(',', $parameterBag->get('ldap_server_individualName'));
        $this->USERDN = explode(';', $this->paramterBag->get('ldap_user_dn'));
        $this->URL = explode(';', $this->paramterBag->get('ldap_url'));
        $this->em = $entityManager;
    }

    protected function configure(): void
    {
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->text('Here you can remove all users from an LDAP server');
        $io->text('Select your server');
        $counter = 0;
        foreach ($this->LDAPSERVERID as $data) {
            $io->text('[' . $counter . '] ' . $this->URL[$counter] . '(' . $data . ')');
            $counter++;
        }
        $selection = $io->ask('Select your Server');
        if ($selection > $counter) {
            $io->error('This number not machting a server Id');
            return Command::FAILURE;
        }
        $confirm = $io->confirm('Do you realy want to delete all Users?', false);

        if (!$confirm) {
            $io->warning('Aborted');
            return Command::FAILURE;
        }
        $io->success('we start to delete');
        $table = new Table($output);
        $table->setHeaderTitle('Removed User');
        $table->setHeaders(['username', 'name','email']);
        $user = $this->em->getRepository(User::class)->findUsersByLdapServerId($this->LDAPSERVERID[$selection]);
        foreach ($user as $data) {
            $this->ldapUserService->deleteUser($data);
            $table->addRow([$data->getUserName(), $data->getFirstname() . ' ' . $data->getLastName(), $data->getEmail()]);
        }
        $table->render();
        return Command::SUCCESS;
    }
}
