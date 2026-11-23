<?php

namespace App\Tests\Twig;

use App\Entity\Server;
use App\Entity\Star;
use App\Twig\ServerStarView;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

class ServerStarViewTest extends TestCase
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

    private function addStar(Server $server, int $value): void
    {
        $star = new Star();
        $star->setStar($value);
        $server->addStar($star);
    }

    public function testGetFunctionsReturnsExpectedTwigFunction(): void
    {
        $extension = new ServerStarView();
        $this->assertSame(["showAverageStar"], $this->functionNames($extension));
    }

    public function testShowAverageStarWithoutStarsReturnsZero(): void
    {
        $extension = new ServerStarView();

        $this->assertSame(0, $extension->showAverageStar(new Server()));
    }

    public function testShowAverageStarReturnsAverage(): void
    {
        $extension = new ServerStarView();
        $server = new Server();
        $this->addStar($server, 5);
        $this->addStar($server, 3);

        $this->assertSame(4, $extension->showAverageStar($server));
    }

    public function testShowAverageStarReturnsFractionalAverage(): void
    {
        $extension = new ServerStarView();
        $server = new Server();
        $this->addStar($server, 5);
        $this->addStar($server, 4);
        $this->addStar($server, 4);

        $this->assertEqualsWithDelta(13 / 3, $extension->showAverageStar($server), 0.0001);
        $this->assertEqualsWithDelta(13 / 3, $extension->showAverageStar($server), 0.0001);
    }
}
