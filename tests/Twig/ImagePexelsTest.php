<?php

namespace App\Tests\Twig;

use App\Service\PexelService;
use App\Twig\ImagePexels;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\TwigFunction;

#[AllowMockObjectsWithoutExpectations]
class ImagePexelsTest extends TestCase
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
        $this->assertSame(["pexelsImage"], $this->functionNames($extension));
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

        $this->assertNull(call_user_func([$extension, 'pexelsImage']));
    }
}
