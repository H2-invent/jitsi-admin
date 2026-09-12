<?php

namespace App\Tests\Service\TermsAndConditions;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\TermsAndConditions\TermsAndConditionsService;
use App\Service\Theme\ThemeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TermsAndConditionsServiceTest extends KernelTestCase
{
    public function testHasAcceptedTermsTrueWhenUserAccepted(): void
    {
        self::bootKernel();
        $themeService = $this->createMock(ThemeService::class);
        $themeService->method('getApplicationProperties')->willReturn('Some terms');
        $service = new TermsAndConditionsService(
            self::getContainer()->get(EntityManagerInterface::class),
            $themeService
        );

        $user = new User();
        $user->setAcceptTermsAndConditions(true);

        self::assertTrue($service->hasAcceptedTerms($user));
    }

    public function testHasAcceptedTermsFalseWhenTermsConfiguredAndNotAccepted(): void
    {
        self::bootKernel();
        $themeService = $this->createMock(ThemeService::class);
        $themeService->expects($this->once())
            ->method('getApplicationProperties')
            ->with('LAF_TERMS_AND_CONDITIONS')
            ->willReturn('Some terms');
        $service = new TermsAndConditionsService(
            self::getContainer()->get(EntityManagerInterface::class),
            $themeService
        );

        $user = new User();
        $user->setAcceptTermsAndConditions(false);

        self::assertFalse($service->hasAcceptedTerms($user));
    }

    public function testHasAcceptedTermsTrueWhenNoTermsConfigured(): void
    {
        self::bootKernel();
        $themeService = $this->createMock(ThemeService::class);
        $themeService->method('getApplicationProperties')->willReturn('');
        $service = new TermsAndConditionsService(
            self::getContainer()->get(EntityManagerInterface::class),
            $themeService
        );

        $user = new User();
        $user->setAcceptTermsAndConditions(false);

        self::assertTrue($service->hasAcceptedTerms($user));
    }

    public function testAcceptTermsPersistsFlag(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        $user = $container->get(UserRepository::class)->findOneBy(['email' => 'test@local.de']);
        $user->setAcceptTermsAndConditions(false);
        $em->flush();
        $userId = $user->getId();

        $service = $container->get(TermsAndConditionsService::class);

        self::assertTrue($service->acceptTerms($user));
        self::assertTrue($user->isAcceptTermsAndConditions());

        $em->clear();
        self::assertTrue($container->get(UserRepository::class)->find($userId)->isAcceptTermsAndConditions());
    }
}
