<?php

namespace App\Tests\LDAP;

use App\dataType\LdapType;
use App\Entity\LdapUserProperties;
use App\Entity\User;
use App\Service\IndexUserService;
use App\Service\ldap\LdapUserService;
use App\Service\UserCreatorService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Ldap\Adapter\AdapterInterface;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\Adapter\CollectionInterface;
use Symfony\Component\Ldap\Adapter\QueryInterface;

class LdapUserServiceDeleteUserTest extends TestCase
{
    private function createService(): LdapUserService
    {
        return $this->getMockBuilder(LdapUserService::class)
            ->setConstructorArgs([
                $this->createMock(LoggerInterface::class),
                $this->createMock(EntityManagerInterface::class),
                $this->createMock(UserCreatorService::class),
                $this->createMock(IndexUserService::class),
            ])
            ->onlyMethods(['deleteUser'])
            ->getMock();
    }

    public function testReturnsNullIfUserHasNoLdapProperties(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getLdapUserProperties')->willReturn(null);

        $ldapType = $this->createMock(LdapType::class);

        $service = $this->createService();
        $service->expects($this->never())->method('deleteUser');

        $this->assertNull($service->checkUserInLdap($user, $ldapType));
    }

    public function testDeletesUserIfNoEntryFound(): void
    {
        // User, der im LDAP gesucht wird
        $user = $this->createMock(User::class);

        // interner LDAP-User (wichtig!)
        $ldapUser = $this->createMock(User::class);
        $ldapUser->method('getUsername')->willReturn('testuser');

        $ldapProps = $this->createMock(LdapUserProperties::class);
        $ldapProps->method('getUser')->willReturn($ldapUser);

        $user->method('getLdapUserProperties')->willReturn($ldapProps);

        // LDAP Result = leer → User löschen
        $collection = $this->createMock(CollectionInterface::class);
        $collection->method('toArray')->willReturn([]);

        $query = $this->createMock(QueryInterface::class);
        $query->method('execute')->willReturn($collection);

        // Adapter statt Ldap mocken
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->method('createQuery')->willReturn($query);

        $ldap = new Ldap($adapter);

        $ldapType = $this->createConfiguredMock(LdapType::class, [
            'getUserDn' => 'ou=users,dc=example,dc=com',
            'buildObjectClass' => '(objectClass=person)',
            'getUserNameAttribute' => 'uid',
            'getLdap' => $ldap,
        ]);

        $service = $this->createService();
        $service->expects($this->once())->method('deleteUser')->with($user);

        $this->assertNull($service->checkUserInLdap($user, $ldapType));
    }

    public function testReturnsEntryIfUserExists(): void
    {
        $entry = new Entry('uid=testuser,ou=users,dc=example,dc=com');

        // interner LDAP-User
        $ldapUser = $this->createMock(User::class);
        $ldapUser->method('getUsername')->willReturn('testuser');

        $ldapProps = $this->createMock(LdapUserProperties::class);
        $ldapProps->method('getUser')->willReturn($ldapUser);

        $user = $this->createMock(User::class);
        $user->method('getLdapUserProperties')->willReturn($ldapProps);

        $collection = $this->createMock(CollectionInterface::class);
        $collection->method('toArray')->willReturn([$entry]);

        $query = $this->createMock(QueryInterface::class);
        $query->method('execute')->willReturn($collection);

        // Adapter mocken, nicht Ldap
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->method('createQuery')->willReturn($query);

        $ldap = new Ldap($adapter);

        $ldapType = $this->createConfiguredMock(LdapType::class, [
            'getUserDn' => 'ou=users,dc=example,dc=com',
            'buildObjectClass' => '(objectClass=person)',
            'getUserNameAttribute' => 'uid',
            'getLdap' => $ldap,
        ]);

        $service = $this->createService();
        $service->expects($this->never())->method('deleteUser');

        $result = $service->checkUserInLdap($user, $ldapType);

        $this->assertSame($entry, $result);
    }


    public function testReturnsNullOnConnectionException(): void
    {
        // interner LDAP-User
        $ldapUser = $this->createMock(User::class);
        $ldapUser->method('getUsername')->willReturn('testuser');

        $ldapProps = $this->createMock(LdapUserProperties::class);
        $ldapProps->method('getUser')->willReturn($ldapUser);

        $user = $this->createMock(User::class);
        $user->method('getLdapUserProperties')->willReturn($ldapProps);

        // Adapter wirft ConnectionException
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->method('createQuery')
            ->willThrowException(new ConnectionException('LDAP down'));

        $ldap = new Ldap($adapter);

        $ldapType = $this->createConfiguredMock(LdapType::class, [
            'getUserDn' => 'ou=users,dc=example,dc=com',
            'buildObjectClass' => '(objectClass=person)',
            'getUserNameAttribute' => 'uid',
            'getLdap' => $ldap,
        ]);

        $service = $this->createService();
        $service->expects($this->never())->method('deleteUser');

        $this->assertNull($service->checkUserInLdap($user, $ldapType));
    }
}
