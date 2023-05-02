<?php

namespace App\Tests\FormatedName;

use App\Repository\UserRepository;
use App\Service\FormatName;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FormatedNameServiceTest extends KernelTestCase
{
    public function testCreateFormatedName(): void
    {
        $kernel = self::bootKernel();
        $formatedNameService = self::getContainer()->get(FormatName::class);

        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        self::assertEquals('Test', $formatedNameService->formatName('user.firstName$', $user));
        self::assertEquals('User', $formatedNameService->formatName('user.lastName$', $user));
        self::assertEquals('test@local.de', $formatedNameService->formatName('user.email$', $user));
        self::assertEquals('test@local.de', $formatedNameService->formatName('user.username$', $user));
        self::assertEquals('test@local.de test@local.de', $formatedNameService->formatName('user.username$ user.username$', $user));
        self::assertEquals('test@local.de (test@local.de) test@local.de. Test- User', $user->getFormatedName('user.username$ (user.username$) user.email$. user.firstName$- user.lastName$', $user));
        self::assertEquals('Test1', $formatedNameService->formatName('user.specialField.ou$', $user));
        self::assertEquals('0123456789', $formatedNameService->formatName('user.specialField.telephoneNumber$', $user));
        self::assertEquals('test@local.de test@local.de, Test1+-0123456789', $formatedNameService->formatName('user.email$ user.username$, user.specialField.ou$+-user.specialField.telephoneNumber$', $user));
    }

    public function testCreateFormatedNameEmptySpecialField(): void
    {
        $kernel = self::bootKernel();
        $formatedNameService = self::getContainer()->get(FormatName::class);

        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        self::assertEquals(
            'test@local.de test@local.de, Test1+-0123456789',
            $formatedNameService->formatName('user.email$ user.username$, user.specialField.ou$+-user.specialField.telephoneNumber$', $user)
        );
        self::assertEquals(
            'test@local.de test@local.de, Test1',
            $formatedNameService->formatName('user.email$ user.username$, user.specialField.ou$+-user.specialField.outch$', $user)
        );
        self::assertEquals(
            'test@local.de test@local.de +-0123456789',
            $formatedNameService->formatName('user.email$ user.username$, user.specialField.notThere$ +-user.specialField.telephoneNumber$', $user)
        );
    }

    public function testCreateFormatedNameEmptyFields(): void
    {
        $kernel = self::bootKernel();
        $formatedNameService = self::getContainer()->get(FormatName::class);

        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        self::assertEquals(
            'User,+- Test',
            $formatedNameService->formatName('user.lastName$,+- user.firstName$', $user)
        );
        $user->setFirstName('');
        self::assertEquals(
            'User',
            $formatedNameService->formatName('user.lastName$,+- user.firstName$', $user)
        );
    }

    public function testCreateFormatedNameEmptyFieldsSecond(): void
    {
        $kernel = self::bootKernel();
        $formatedNameService = self::getContainer()->get(FormatName::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        self::assertEquals(
            'User,+- Test',
            $formatedNameService->formatName('user.lastName$,+- user.firstName$', $user)
        );
        $user->setLastName('');
        self::assertEquals(
            'Test',
            $formatedNameService->formatName('user.lastName$,+- user.firstName$', $user)
        );

        self::assertEquals(
            'Test1',
            $formatedNameService->formatName('user.lastName$, user.specialField.outch$+-user.specialField.ou$', $user)
        );
        self::assertEquals(
            'Test+-Test1',
            $formatedNameService->formatName('user.firstName$, user.lastName$, user.specialField.outch$+-user.specialField.ou$', $user)
        );
        $user->setFirstName('');
        self::assertEquals(
            'Test1',
            $formatedNameService->formatName('user.firstName$, user.lastName$, user.specialField.outch$+-user.specialField.ou$', $user)
        );
    }

    public function testCreateFormatedNameEmptyStringFieldsSecond(): void
    {
        $kernel = self::bootKernel();
        $formatedNameService = self::getContainer()->get(FormatName::class);
        $this->assertSame('test', $kernel->getEnvironment());
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(['email' => 'test@local.de']);
        self::assertEquals(
            'test@local.de test@local.de +-0123456789',
            $formatedNameService->formatName('user.email$ user.username$, user.specialField.notThere$ +-user.specialField.telephoneNumber$', $user)
        );
        $specialField = $user->getSpezialProperties();
        $specialField['telephoneNumber'] = '';
        $user->setSpezialProperties($specialField);
        self::assertEquals(
            'test@local.de test@local.de',
            $formatedNameService->formatName('user.email$ user.username$, user.specialField.notThere$ +-user.specialField.telephoneNumber$', $user)
        );
    }
}
