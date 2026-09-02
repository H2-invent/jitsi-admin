<?php
namespace App\Tests\Dashboard;

use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class LdapCoverageProbe extends KernelTestCase
{
    public function testProbe()
    {
        $kernel = self::bootKernel();
        $em = $this->getContainer()->get(EntityManagerInterface::class);
        $config = $em->getConnection()->getConfiguration();
        $stack = new DebugStack();
        $config->setSQLLogger($stack);

        $roomRepo = $this->getContainer()->get(RoomsRepository::class);
        $user = $this->getContainer()->get(UserRepository::class)->findOneByUsername('test@local.de');
        $rooms = $roomRepo->findRoomsInFuture($user, null);

        $stack->queries = [];
        foreach ($rooms as $room) {
            foreach ($room->getUser() as $p) {
                $p->getLdapUserProperties();
            }
            if ($room->getModerator()) {
                $room->getModerator()->getLdapUserProperties();
            }
            if ($room->getCreator()) {
                $room->getCreator()->getLdapUserProperties();
            }
        }
        $queries = $stack->queries;
        $ldapQueries = array_filter($queries, fn($q) => str_contains($q['sql'], 'ldap_user_properties'));
        echo "queries after ldap access: " . count($queries) . ", ldap queries: " . count($ldapQueries) . "\n";
        foreach ($queries as $q) {
            echo preg_replace('/\s+/', ' ', $q['sql']) . "\n";
        }
        $config->setSQLLogger(null);
    }
}
