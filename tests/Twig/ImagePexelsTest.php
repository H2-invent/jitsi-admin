<?php

namespace App\Tests\Twig;

use App\Service\PexelService;
use App\Twig\ImagePexels;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\TwigFunction;

class ImagePexelsTest extends TestCase
{
    private function createExtension(PexelService $pexelService): ImagePexels
    {
        return new ImagePexels(
            $pexelService,
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(TokenStorageInterface::class),
            $this->createMock(EntityManagerInterface::class)
        );
    }

    public function testGetFunctionsReturnsExpectedTwigFunction(): void
    {
        $extension = $this->createExtension($this->createMock(PexelService::class));

        $functions = $extension->getFunctions();

        $this->assertCount(1, $functions);
        $this->assertInstanceOf(TwigFunction::class, $functions[0]);
        $this->assertSame('pexelsImage', $functions[0]->getName());
        $this->assertSame([$extension, 'pexelsImage'], $functions[0]->getCallable());
    }

    public function testPexelsImageDelegatesToPexelService(): void
    {
        $image = ['id' => 42, 'url' => 'https://images.pexels.com/photo.jpg'];
        $pexelService = $this->createMock(PexelService::class);
        $pexelService->expects($this->once())->method('getImageFromPexels')->willReturn($image);
        $extension = $this->createExtension($pexelService);

        $this->assertSame($image, $extension->pexelsImage());
    }

    public function testPexelsImageReturnsNullWhenServiceReturnsNull(): void
    {
        $pexelService = $this->createMock(PexelService::class);
        $pexelService->method('getImageFromPexels')->willReturn(null);
        $extension = $this->createExtension($pexelService);

        $this->assertNull(call_user_func($extension->getFunctions()[0]->getCallable()));
    }
}
