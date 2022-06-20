<?php

namespace App\Tests\FormatedName;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FormatedNameTest extends KernelTestCase
{
    public function testCreateFormatedName(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email' => 'test@local.de'));
        self::assertEquals('Test',$user->getFormatedName('user.firstName$'));
        self::assertEquals('User',$user->getFormatedName('user.lastName$'));
        self::assertEquals('test@local.de',$user->getFormatedName('user.email$'));
        self::assertEquals('test@local.de',$user->getFormatedName('user.username$'));
        self::assertEquals('test@local.de test@local.de',$user->getFormatedName('user.username$ user.username$'));
        self::assertEquals('test@local.de (test@local.de) test@local.de. Test- User',$user->getFormatedName('user.username$ (user.username$) user.email$. user.firstName$- user.lastName$'));
        self::assertEquals('Test1',$user->getFormatedName('user.specialField.ou$'));
        self::assertEquals('0123456789',$user->getFormatedName('user.specialField.telephoneNumber$'));
        self::assertEquals('test@local.de test@local.de, Test1+-0123456789',$user->getFormatedName('user.email$ user.username$, user.specialField.ou$+-user.specialField.telephoneNumber$'));
    }

    public function testCreateFormatedNameEmptySpecialField(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('email' => 'test@local.de'));
        self::assertEquals(
            'test@local.de test@local.de, Test1+-0123456789',
            $user->getFormatedName('user.email$ user.username$, user.specialField.ou$+-user.specialField.telephoneNumber$')
        );
        self::assertEquals(
            'test@local.de test@local.de, Test1',
            $user->getFormatedName('user.email$ user.username$, user.specialField.ou$+-user.specialField.outch$')
        );
        self::assertEquals(
            'test@local.de test@local.de +-0123456789',
            $user->getFormatedName('user.email$ user.username$, user.specialField.notThere$ +-user.specialField.telephoneNumber$')
        );
    }
}
