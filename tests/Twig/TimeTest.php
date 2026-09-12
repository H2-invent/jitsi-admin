<?php

namespace App\Tests\Twig;

use App\Entity\User;
use App\Twig\Time;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\TwigFunction;

class TimeTest extends KernelTestCase
{
    private function extension(): Time
    {
        return self::getContainer()->get(Time::class);
    }

    private function functionByName(Time $extension, string $name): TwigFunction
    {
        foreach ($extension->getFunctions() as $function) {
            if ($function->getName() === $name) {
                return $function;
            }
        }
        self::fail(sprintf('Twig function "%s" not registered', $name));
    }

    public function testGetFunctionsReturnsExpectedTwigFunction(): void
    {
        self::bootKernel();
        $extension = $this->extension();

        $functions = $extension->getFunctions();

        $this->assertCount(1, $functions);
        $this->assertSame('getTime', $functions[0]->getName());
        $this->assertSame([$extension, 'getTime'], $functions[0]->getCallable());
    }

    public function testGetTimeReturnsCurrentTimeInUserTimeZone(): void
    {
        self::bootKernel();
        $extension = $this->extension();
        $user = (new User())->setTimeZone('Australia/Lindeman');
        $before = time();
        $result = $extension->getTime($user);
        $after = time();

        $this->assertInstanceOf(\DateTime::class, $result);
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
        $this->assertSame(
            $result->getTimestamp(),
            call_user_func($this->functionByName($extension, 'getTime')->getCallable(), $user)->getTimestamp()
        );
    }
}
