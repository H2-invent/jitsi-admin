<?php

namespace App\Tests\Twig;

use App\Entity\User;
use App\Twig\OnlineStatus;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\TwigFunction;

class OnlineStatusTest extends KernelTestCase
{

    private function functionNames(object $extension): array
    {
        $names = [];
        foreach ((new \ReflectionClass($extension))->getMethods() as $method) {
            foreach ($method->getAttributes(\Twig\Attribute\AsTwigFunction::class) as $attribute) {
                $names[] = $attribute->newInstance()->name;
            }
        }
        return $names;
    }

    private function filterNames(object $extension): array
    {
        $names = [];
        foreach ((new \ReflectionClass($extension))->getMethods() as $method) {
            foreach ($method->getAttributes(\Twig\Attribute\AsTwigFilter::class) as $attribute) {
                $names[] = $attribute->newInstance()->name;
            }
        }
        return $names;
    }

    private function extension(): OnlineStatus
    {
        return self::getContainer()->get(OnlineStatus::class);
    }

    private function functionByName(OnlineStatus $extension, string $name): string
    {
        foreach ((new \ReflectionClass($extension))->getMethods() as $method) {
            foreach ($method->getAttributes(\Twig\Attribute\AsTwigFunction::class) as $attribute) {
                if ($attribute->newInstance()->name === $name) {
                    return $method->getName();
                }
            }
        }
        self::fail(sprintf('Twig Function "%s" not registered', $name));
    }

    public function testGetFunctionsReturnsExpectedTwigFunctions(): void
    {
        $extension = $this->extension();
        $this->assertSame(["getOnlineStatus", "getOnlineStatusString"], $this->functionNames($extension));
    }

    public function testGetOnlineStatusReturnsOnlineForStatusOne(): void
    {
        self::bootKernel();
        $extension = $this->extension();
        $user = (new User())->setOnlineStatus(1);

        $this->assertSame('online', $extension->getOnlineStatus($user));
        $this->assertSame(
            'online',
            call_user_func([$extension, $this->functionByName($extension, 'getOnlineStatus')], $user)
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
            call_user_func([$extension, $this->functionByName($extension, 'getOnlineStatusString')], (new User())->setOnlineStatus(1))
        );
    }
}
