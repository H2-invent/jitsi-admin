<?php

namespace App\Tests\Twig;

use App\Entity\User;
use App\Twig\Time;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\TwigFunction;

class TimeTest extends KernelTestCase
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

    private function extension(): Time
    {
        return self::getContainer()->get(Time::class);
    }

    public function testGetFunctionsReturnsExpectedTwigFunction(): void
    {
        $extension = $this->extension();
        $this->assertSame(["getTime"], $this->functionNames($extension));
    }

    public function testGetTimeReturnsCurrentTimeInUserTimeZone(): void
    {
        self::bootKernel();
        $extension = $this->extension();
        $user = (new User())->setTimeZone('Australia/Lindeman');
        $before = time();
        $result = $extension->getTime($user);
        $after = time();

        $this->assertInstanceOf(\DateTimeImmutable::class, $result);
        $this->assertSame('Australia/Lindeman', $result->getTimezone()->getName());
        $this->assertGreaterThanOrEqual($before, $result->getTimestamp());
        $this->assertLessThanOrEqual($after, $result->getTimestamp());
    }

    public function testGetTimeUsesEuropeanTimeZoneOfUser(): void
    {
        self::bootKernel();
        $extension = $this->extension();
        $user = (new User())->setTimeZone('Europe/Berlin');

        $result = $extension->getTime($user);

        $this->assertSame('Europe/Berlin', $result->getTimezone()->getName());
    }
}
