<?php

namespace App\Tests\Twig;

use App\Entity\User;
use App\Twig\OnlineStatus;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\TwigFunction;

class OnlineStatusTest extends KernelTestCase
{
    private function extension(): OnlineStatus
    {
        return self::getContainer()->get(OnlineStatus::class);
    }

    private function functionByName(OnlineStatus $extension, string $name): TwigFunction
    {
        foreach ($extension->getFunctions() as $function) {
            if ($function->getName() === $name) {
                return $function;
            }
        }
        self::fail(sprintf('Twig function "%s" not registered', $name));
    }

    public function testGetFunctionsReturnsExpectedTwigFunctions(): void
    {
        self::bootKernel();
        $extension = $this->extension();

        $functions = $extension->getFunctions();
        $names = array_map(static fn(TwigFunction $function) => $function->getName(), $functions);

        $this->assertSame(['getOnlineStatus', 'getOnlineStatusString'], $names);
        $this->assertSame([$extension, 'getOnlineStatus'], $functions[0]->getCallable());
        $this->assertSame([$extension, 'getOnlineStatusString'], $functions[1]->getCallable());
    }

    public function testGetOnlineStatusReturnsOnlineForStatusOne(): void
    {
        self::bootKernel();
        $extension = $this->extension();
        $user = (new User())->setOnlineStatus(1);

        $this->assertSame('online', $extension->getOnlineStatus($user));
        $this->assertSame(
            'online',
            call_user_func($this->functionByName($extension, 'getOnlineStatus')->getCallable(), $user)
        );
    }

    public function testGetOnlineStatusReturnsOfflineForOtherStatuses(): void
    {
        self::bootKernel();
        $extension = $this->extension();

        $this->assertSame('offline', $extension->getOnlineStatus((new User())->setOnlineStatus(2)));
        $this->assertSame('offline', $extension->getOnlineStatus((new User())->setOnlineStatus(0)));
    }

    public function testGetOnlineStatusUsesDefaultStatusWhenUserHasNoStatus(): void
    {
        self::bootKernel();
        $extension = $this->extension();

        $this->assertSame('online', $extension->getOnlineStatus(new User()));
    }

    public function testGetOnlineStatusStringReturnsTranslatedStatus(): void
    {
        self::bootKernel();
        $extension = $this->extension();

        $this->assertSame('Online', $extension->getOnlineStatusString((new User())->setOnlineStatus(1)));
        $this->assertSame('Offline', $extension->getOnlineStatusString((new User())->setOnlineStatus(2)));
        $this->assertSame(
            'Online',
            call_user_func($this->functionByName($extension, 'getOnlineStatusString')->getCallable(), (new User())->setOnlineStatus(1))
        );
    }
}
