<?php

namespace App\Tests\Twig;

use App\Entity\Server;
use App\Entity\Star;
use App\Twig\ServerStarView;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

class ServerStarViewTest extends TestCase
{
    private function addStar(Server $server, int $value): void
    {
        $star = new Star();
        $star->setStar($value);
        $server->addStar($star);
    }

    public function testGetFunctionsReturnsExpectedTwigFunction(): void
    {
        $extension = new ServerStarView();

        $functions = $extension->getFunctions();

        $this->assertCount(1, $functions);
        $this->assertInstanceOf(TwigFunction::class, $functions[0]);
        $this->assertSame('showAverageStar', $functions[0]->getName());
        $this->assertSame([$extension, 'showAverageStar'], $functions[0]->getCallable());
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
        $this->assertEqualsWithDelta(13 / 3, call_user_func($extension->getFunctions()[0]->getCallable(), $server), 0.0001);
    }
}
