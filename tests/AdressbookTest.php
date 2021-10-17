<?php

namespace App\Tests;

use App\Repository\AddressGroupRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AdressbookTest extends KernelTestCase
{
    public const SEARCHUSERPOSITIVE = [
        'test@local2.de',
        'local2.de',
        'test',
        'Test',
        'User',
        '1234',
        'Test1'
    ];

    public function testSomething(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $userRepo = self::$container->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email' => 'test@local.de'));
        $userfind = $userRepo->findOneBy(array('email' => 'test@local2.de'));
        $groupRepo = $this->getContainer()->get(AddressGroupRepository::class);
        $groupFind = $groupRepo->findOneBy(array('name' => 'Testgruppe'));
        $userRepo->findMyUserByEmail('test@local2.de', $user);
        foreach (self::SEARCHUSERPOSITIVE as $data){
            self::assertEquals($userfind, $userRepo->findMyUserByEmail($data, $user)[0]);
        }
        self::assertEquals(0, sizeof($userRepo->findMyUserByEmail('User12', $user)));
        self::assertEquals($userfind, $userRepo->findMyUserByEmail('Test1', $user)[0]);
        self::assertEquals($groupFind, $groupRepo->findMyAddressBookGroupsByName('Testgr', $user)[0]);
        self::assertEquals($groupFind, $groupRepo->findMyAddressBookGroupsByName('Test', $user)[0]);
        self::assertEquals(0, sizeof($groupRepo->findMyAddressBookGroupsByName('Testwe', $user)));
    }
}
