<?php

namespace App\Tests\Addressbook;

use App\Repository\AddressGroupRepository;
use App\Repository\UserRepository;
use App\Service\ParticipantSearchService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AdressbookTest extends KernelTestCase
{
    public const SEARCHUSERPOSITIVE = [
        'test@local2.de',
        'local2.de',
        'test',
        'Test2',
        'User2',
        '1234',
        'Test2'
    ];

    public function testfindUserandGroups(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $userRepo = self::$container->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email' => 'test@local.de'));
        $userfind = $userRepo->findOneBy(array('email' => 'test@local2.de'));
        $groupRepo = $this->getContainer()->get(AddressGroupRepository::class);
        $groupFind = $groupRepo->findOneBy(array('name' => 'Testgruppe'));
        $userRepo->findMyUserByIndex('test@local2.de', $user);
        foreach (self::SEARCHUSERPOSITIVE as $data){
            self::assertEquals($userfind, $userRepo->findMyUserByIndex($data, $user)[0]);
        }
        self::assertEquals(0, sizeof($userRepo->findMyUserByIndex('User12', $user)));
        self::assertEquals($userfind, $userRepo->findMyUserByIndex('Test2', $user)[0]);
        self::assertEquals($groupFind, $groupRepo->findMyAddressBookGroupsByName('Testgr', $user)[0]);
        self::assertEquals($groupFind, $groupRepo->findMyAddressBookGroupsByName('Test', $user)[0]);
        self::assertEquals(0, sizeof($groupRepo->findMyAddressBookGroupsByName('Testwe', $user)));
    }
    public function testgenerateUSer(): void
    {
        $kernel = self::bootKernel();
        $searchService = $this->getContainer()->get(ParticipantSearchService::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $userRepo = self::$container->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email' => 'test@local.de'));
        $userfind = $userRepo->findOneBy(array('email' => 'test@local2.de'));
        $groupRepo = $this->getContainer()->get(AddressGroupRepository::class);
        $groupFind = $groupRepo->findOneBy(array('name' => 'Testgruppe'));
        $userRepo->findMyUserByIndex('test@local2.de', $user);
        $string = 'test';
        $userArr = $userRepo->findMyUserByIndex($string, $user);
        $res = $searchService->generateUserwithEmptyUser($userArr,$string);
        $this->assertEquals(array(
            array('name'=>'<i class="fa fa-phone" title="9876543210" data-toggle="tooltip"></i> Test2, 1234, User2, Test2','id'=>"test2@local.de"),
            array('name'=>'','id'=>"test@local3.de")
        ),$res);
        $string = '1234';
        $userArr = $userRepo->findMyUserByIndex($string, $user);
        $res = $searchService->generateUserwithEmptyUser($userArr,$string);
        $this->assertEquals(array(
            array('name'=>'<i class="fa fa-phone" title="9876543210" data-toggle="tooltip"></i> Test2, 1234, User2, Test2','id'=>"test2@local.de"),
        ),$res);
        $string = 'asdf';
        $userArr = $userRepo->findMyUserByIndex($string, $user);
        $res = $searchService->generateUserwithEmptyUser($userArr,$string);
        $this->assertEquals(array(
            array('name'=>"asdf",'id'=>"asdf"),
        ),$res);
        $string = 'TEst2';
        $userArr = $userRepo->findMyUserByIndex($string, $user);
        $res = $searchService->generateUserwithEmptyUser($userArr,$string);
        $this->assertEquals(array(
            array('name'=>'<i class="fa fa-phone" title="9876543210" data-toggle="tooltip"></i> Test2, 1234, User2, Test2','id'=>"test2@local.de"),
        ),$res);

    }
    public function testNoUserFoundandGenerate(): void
    {
        $kernel = self::bootKernel();
        $searchService = $this->getContainer()->get(ParticipantSearchService::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $userRepo = self::$container->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email' => 'test@local.de'));
        $userfind = $userRepo->findOneBy(array('email' => 'test@local2.de'));
        $groupRepo = $this->getContainer()->get(AddressGroupRepository::class);
        $groupFind = $groupRepo->findOneBy(array('name' => 'Testgruppe'));
        $userRepo->findMyUserByIndex('test@local2.de', $user);
        $string = 'asdf';
        $userArr = $userRepo->findMyUserByIndex($string, $user);
        $res = $searchService->generateUserwithEmptyUser($userArr,$string);
        $this->assertEquals(array(
            array('name'=>$string, 'id'=>$string)
        ),$res);

    }
    public function testUserFoundandGenerate(): void
    {
        $kernel = self::bootKernel();
        $searchService = $this->getContainer()->get(ParticipantSearchService::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $userRepo = self::$container->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email' => 'test@local.de'));
        $userRepo->findMyUserByIndex('test@local2.de', $user);
        $string = 'test';
        $userArr = $userRepo->findMyUserByIndex($string, $user);
        $res = $searchService->generateUserwithoutEmptyUser($userArr);
        $this->assertEquals(array(
            array('name'=>'<i class="fa fa-phone" title="9876543210" data-toggle="tooltip"></i> Test2, 1234, User2, Test2','id'=>"test2@local.de"),
            array('name'=>'','id'=>"test@local3.de")
        ),$res);
        $string = '1234';
        $userArr = $userRepo->findMyUserByIndex($string, $user);
        $res = $searchService->generateUserwithoutEmptyUser($userArr);
        $this->assertEquals(array(
            array('name'=>'<i class="fa fa-phone" title="9876543210" data-toggle="tooltip"></i> Test2, 1234, User2, Test2','id'=>"test2@local.de"),
        ),$res);
    }
    public function testnoUSerfoundNoGenerate(): void
    {
        $kernel = self::bootKernel();
        $searchService = $this->getContainer()->get(ParticipantSearchService::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $userRepo = self::$container->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email' => 'test@local.de'));
        $userRepo->findMyUserByIndex('test@local2.de', $user);
        $string = 'asdf';
        $userArr = $userRepo->findMyUserByIndex($string, $user);
        $res = $searchService->generateUserwithoutEmptyUser($userArr,$string);
        $this->assertEquals(array(
        ),$res);
    }
    public function testUserFoundNoGenerate(): void
    {
        $kernel = self::bootKernel();
        $searchService = $this->getContainer()->get(ParticipantSearchService::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $userRepo = self::$container->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email' => 'test@local.de'));
        $userfind = $userRepo->findOneBy(array('email' => 'test@local2.de'));
        $groupRepo = $this->getContainer()->get(AddressGroupRepository::class);
        $groupFind = $groupRepo->findOneBy(array('name' => 'Testgruppe'));
        $userRepo->findMyUserByIndex('test@local2.de', $user);
        $string = 'test';
        $userArr = $userRepo->findMyUserByIndex($string, $user);
        $res = $searchService->generateUserwithoutEmptyUser($userArr);
        $this->assertEquals(array(
            array('name'=>'<i class="fa fa-phone" title="9876543210" data-toggle="tooltip"></i> Test2, 1234, User2, Test2','id'=>"test2@local.de"),
            array('name'=>'','id'=>"test@local3.de")
        ),$res);
        $string = '1234';
        $userArr = $userRepo->findMyUserByIndex($string, $user);
        $res = $searchService->generateUserwithoutEmptyUser($userArr);
        $this->assertEquals(array(
            array('name'=>'<i class="fa fa-phone" title="9876543210" data-toggle="tooltip"></i> Test2, 1234, User2, Test2','id'=>"test2@local.de"),
        ),$res);

    }

    public function testgroupFound(): void
    {
        $kernel = self::bootKernel();
        $searchService = $this->getContainer()->get(ParticipantSearchService::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $userRepo = self::$container->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email' => 'test@local.de'));
        $groupRepo = $this->getContainer()->get(AddressGroupRepository::class);
        $userRepo->findMyUserByIndex('test@local2.de', $user);
        $string = 'test';
        $userGroup = $groupRepo->findMyAddressBookGroupsByName($string, $user);
        $res = $searchService->generateGroup($userGroup);
        $this->assertEquals(array(
            array('name' => "Testgruppe", 'user' => "test2@local.de\ntest@local3.de"),
        ), $res);
        $string = 'Testgruppe';
        $userGroup = $groupRepo->findMyAddressBookGroupsByName($string, $user);
        $res = $searchService->generateGroup($userGroup);
        $this->assertEquals(array(
            array('name' => "Testgruppe", 'user' => "test2@local.de\ntest@local3.de"),
        ), $res);
        $string = 'testio';
        $userGroup = $groupRepo->findMyAddressBookGroupsByName($string, $user);
        $res = $searchService->generateGroup($userGroup);
        $this->assertEquals(array(), $res);

    }

        public function testNogroupFound(): void
    {
        $kernel = self::bootKernel();
        $searchService = $this->getContainer()->get(ParticipantSearchService::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $userRepo = self::$container->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email' => 'test@local.de'));
        $groupRepo = $this->getContainer()->get(AddressGroupRepository::class);
        $userRepo->findMyUserByIndex('test@local2.de', $user);
        $string = 'test';
        $userGroup = $groupRepo->findMyAddressBookGroupsByName($string, $user);
        $res = $searchService->generateGroup($userGroup);
        $string = 'testio';
        $userGroup = $groupRepo->findMyAddressBookGroupsByName($string, $user);
        $res = $searchService->generateGroup($userGroup);
        $this->assertEquals(array(
        ),$res);

    }
    public function testgenerateName(): void
    {
        $kernel = self::bootKernel();
        $searchService = $this->getContainer()->get(ParticipantSearchService::class);
        $userRepo = self::$container->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email' => 'test@local.de'));
        $res = $searchService->buildShowInFrontendString($user);
        self::assertEquals('<i class="fa fa-phone" title="0123456789" data-toggle="tooltip"></i> Test1, 1234, User, Test', $res);
    }

    public function testgenerateNameNoIcon(): void
    {
        $kernel = self::bootKernel();
        $searchService = $this->getContainer()->get(ParticipantSearchService::class);
        $userRepo = self::$container->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email' => 'test@local.de'));
        $res = $searchService->buildShowInFrontendStringNoString($user);
        self::assertEquals('Test1, 1234, User, Test', $res);
    }
}
