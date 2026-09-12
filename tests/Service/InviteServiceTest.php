<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\InviteService;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class InviteServiceTest extends KernelTestCase
{
    public function testConnectUserWithEmailCopiesMissingTeamAndAkademieData(): void
    {
        $team = new \stdClass();
        $akademie = new \stdClass();
        $teamDsb = new \stdClass();

        $userFromRegister = $this->getMockBuilder(User::class)
            ->addMethods(['getTeam', 'getAkademieUser'])
            ->getMock();
        $userFromRegister->method('getTeam')->willReturn($team);
        $userFromRegister->method('getAkademieUser')->willReturn($akademie);

        $user = $this->getMockBuilder(User::class)
            ->addMethods(['getTeam', 'setTeam', 'getAkademieUser', 'setAkademieUser', 'getTeamDsb', 'addTeamDsb'])
            ->getMock();
        $user->method('getTeam')->willReturn(null);
        $user->expects($this->once())->method('setTeam')->with($team);
        $user->method('getAkademieUser')->willReturn(null);
        $user->expects($this->once())->method('setAkademieUser')->with($akademie);
        $user->method('getTeamDsb')->willReturn([$teamDsb]);
        $user->expects($this->once())->method('addTeamDsb')->with($teamDsb);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($user);
        $em->expects($this->once())->method('remove')->with($userFromRegister);
        $em->expects($this->once())->method('flush');

        $service = $this->createService($em);

        self::assertSame($user, $service->connectUserWithEmail($userFromRegister, $user));
    }

    public function testConnectUserWithEmailKeepsExistingTeamAndAkademieData(): void
    {
        $existingTeam = new \stdClass();
        $existingAkademie = new \stdClass();

        $userFromRegister = $this->getMockBuilder(User::class)
            ->addMethods(['getTeam', 'getAkademieUser'])
            ->getMock();
        $userFromRegister->method('getTeam')->willReturn(new \stdClass());
        $userFromRegister->method('getAkademieUser')->willReturn(new \stdClass());

        $user = $this->getMockBuilder(User::class)
            ->addMethods(['getTeam', 'setTeam', 'getAkademieUser', 'setAkademieUser', 'getTeamDsb', 'addTeamDsb'])
            ->getMock();
        $user->method('getTeam')->willReturn($existingTeam);
        $user->expects($this->never())->method('setTeam');
        $user->method('getAkademieUser')->willReturn($existingAkademie);
        $user->expects($this->never())->method('setAkademieUser');
        $user->method('getTeamDsb')->willReturn([]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($user);
        $em->expects($this->once())->method('remove')->with($userFromRegister);
        $em->expects($this->once())->method('flush');

        $service = $this->createService($em);

        self::assertSame($user, $service->connectUserWithEmail($userFromRegister, $user));
    }

    public function testRealUserEntitiesCannotBeConnectedBecauseTeamMethodsAreMissing(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $service = new InviteService(
            $container->get(Environment::class),
            $container->get(ParameterBagInterface::class),
            $container->get(MailerService::class),
            $container->get(EntityManagerInterface::class),
            $container->get(TranslatorInterface::class),
            $container->get(UrlGeneratorInterface::class)
        );

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('getTeam');

        $service->connectUserWithEmail(new User(), new User());
    }

    private function createService(EntityManagerInterface $em): InviteService
    {
        return new InviteService(
            $this->createMock(Environment::class),
            $this->createMock(ParameterBagInterface::class),
            $this->createMock(MailerService::class),
            $em,
            $this->createMock(TranslatorInterface::class),
            $this->createMock(UrlGeneratorInterface::class)
        );
    }
}
