<?php

namespace App\Tests\LDAP;

use App\dataType\LdapType;
use App\Service\IndexUserService;
use App\Service\ldap\LdapUserService;
use App\Service\UserCreatorService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Ldap\Adapter\CollectionInterface;
use Symfony\Component\Ldap\Adapter\QueryInterface;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Exception\LdapException;
use Symfony\Component\Ldap\LdapInterface;

class LdapHotstandbyTest extends TestCase
{
    private function makeService(): LdapUserService
    {
        return new LdapUserService(
            new NullLogger(),
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(UserCreatorService::class),
            $this->createMock(IndexUserService::class),
        );
    }

    /**
     * Build a mocked LdapInterface where each configured group DN resolves to
     * a fixed list of `member` DNs. Group DNs not in the map throw a
     * not-found LdapException, matching real-server behaviour.
     *
     * @param array<string, string[]> $groupToMembers DN (lowercased) → member DNs
     */
    private function mockLdap(array $groupToMembers): LdapInterface
    {
        $ldap = $this->createMock(LdapInterface::class);
        $ldap->method('query')->willReturnCallback(function (string $dn) use ($groupToMembers): QueryInterface {
            $key = strtolower(trim($dn));
            $query = $this->createMock(QueryInterface::class);
            if (!array_key_exists($key, $groupToMembers)) {
                $query->method('execute')->willThrowException(new LdapException('No such object: ' . $dn));
                return $query;
            }
            $entry = new Entry($dn, [
                'cn' => [$this->cnFromDn($dn)],
                'member' => $groupToMembers[$key],
            ]);
            $collection = $this->createMock(CollectionInterface::class);
            // Fresh iterator on every getIterator() call — Symfony's real
            // Collection is rewindable; an ArrayIterator captured in
            // willReturn() is consumed once and would mask regressions where
            // code iterates the collection more than once.
            $collection->method('getIterator')->willReturnCallback(
                static fn (): \ArrayIterator => new \ArrayIterator([$entry]),
            );
            $collection->method('count')->willReturn(1);
            $query->method('execute')->willReturn($collection);
            return $query;
        });
        return $ldap;
    }

    private function cnFromDn(string $dn): string
    {
        foreach (explode(',', $dn) as $component) {
            $component = trim($component);
            if (stripos($component, 'cn=') === 0) {
                return substr($component, 3);
            }
        }
        return $dn;
    }

    public function testReturnsNullWhenFeatureNotConfigured(): void
    {
        $service = $this->makeService();
        $ldapType = new LdapType();
        $ldapType->setLDAPHOTSTANDBYGROUPDN([]);
        $ldapType->setLdap($this->mockLdap([]));

        $entry = new Entry('uid=alice,ou=people,dc=ex,dc=com', []);

        $this->assertNull($service->resolveHotstandbyId($entry, $ldapType));
    }

    public function testReturnsNullWhenLdapConnectionMissing(): void
    {
        $service = $this->makeService();
        $ldapType = new LdapType();
        $ldapType->setLDAPHOTSTANDBYGROUPDN(['cn=hotstandby-a,ou=groups,dc=ex,dc=com']);

        $entry = new Entry('uid=alice,ou=people,dc=ex,dc=com', []);

        $this->assertNull($service->resolveHotstandbyId($entry, $ldapType));
    }

    public function testReturnsNullWhenNoConfiguredGroupListsTheUser(): void
    {
        $service = $this->makeService();
        $ldapType = new LdapType();
        $ldapType->setLDAPHOTSTANDBYGROUPDN(['cn=hotstandby-a,ou=groups,dc=ex,dc=com']);
        $ldapType->setLdap($this->mockLdap([
            'cn=hotstandby-a,ou=groups,dc=ex,dc=com' => [
                'uid=bob,ou=people,dc=ex,dc=com',
            ],
        ]));

        $entry = new Entry('uid=alice,ou=people,dc=ex,dc=com', []);

        $this->assertNull($service->resolveHotstandbyId($entry, $ldapType));
    }

    public function testReturnsCnWhenUserIsAMember(): void
    {
        $service = $this->makeService();
        $userDn = 'uid=alice,ou=people,dc=ex,dc=com';

        $ldapType = new LdapType();
        $ldapType->setLDAPHOTSTANDBYGROUPDN(['cn=hotstandby-a,ou=groups,dc=ex,dc=com']);
        $ldapType->setLdap($this->mockLdap([
            'cn=hotstandby-a,ou=groups,dc=ex,dc=com' => [
                'uid=bob,ou=people,dc=ex,dc=com',
                $userDn,
            ],
        ]));

        $entry = new Entry($userDn, []);

        $this->assertSame('hotstandby-a', $service->resolveHotstandbyId($entry, $ldapType));
    }

    public function testMatchesCaseInsensitively(): void
    {
        $service = $this->makeService();
        $userDn = 'uid=alice,ou=people,dc=ex,dc=com';

        $ldapType = new LdapType();
        $ldapType->setLDAPHOTSTANDBYGROUPDN(['CN=Hotstandby-A,OU=Groups,DC=Ex,DC=Com']);
        $ldapType->setLdap($this->mockLdap([
            'cn=hotstandby-a,ou=groups,dc=ex,dc=com' => [$userDn],
        ]));

        $entry = new Entry($userDn, []);

        $this->assertSame('Hotstandby-A', $service->resolveHotstandbyId($entry, $ldapType));
    }

    public function testReturnsFirstMatchWhenMultipleConfiguredGroupsListTheUser(): void
    {
        $service = $this->makeService();
        $userDn = 'uid=alice,ou=people,dc=ex,dc=com';

        $ldapType = new LdapType();
        $ldapType->setLDAPHOTSTANDBYGROUPDN([
            'cn=hotstandby-a,ou=groups,dc=ex,dc=com',
            'cn=hotstandby-b,ou=groups,dc=ex,dc=com',
        ]);
        $ldapType->setLdap($this->mockLdap([
            'cn=hotstandby-a,ou=groups,dc=ex,dc=com' => [$userDn],
            'cn=hotstandby-b,ou=groups,dc=ex,dc=com' => [$userDn],
        ]));

        $entry = new Entry($userDn, []);

        $this->assertSame('hotstandby-a', $service->resolveHotstandbyId($entry, $ldapType));
    }

    public function testSkipsMissingGroupsAndContinues(): void
    {
        $service = $this->makeService();
        $userDn = 'uid=alice,ou=people,dc=ex,dc=com';

        $ldapType = new LdapType();
        $ldapType->setLDAPHOTSTANDBYGROUPDN([
            'cn=does-not-exist,ou=groups,dc=ex,dc=com',
            'cn=hotstandby-b,ou=groups,dc=ex,dc=com',
        ]);
        $ldapType->setLdap($this->mockLdap([
            'cn=hotstandby-b,ou=groups,dc=ex,dc=com' => [$userDn],
        ]));

        $entry = new Entry($userDn, []);

        $this->assertSame('hotstandby-b', $service->resolveHotstandbyId($entry, $ldapType));
    }
}
