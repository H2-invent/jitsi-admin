<?php

namespace App\Tests\Twig;

use App\Repository\RoomsRepository;
use App\Service\Jigasi\JigasiService;
use App\Twig\Jigasi;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Twig\TwigFunction;

class JigasiTest extends KernelTestCase
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

    private function extension(): Jigasi
    {
        return self::getContainer()->get(Jigasi::class);
    }

    public function testGetFunctionsReturnsExpectedTwigFunctions(): void
    {
        $extension = $this->extension();
        $this->assertSame(["getJigasiNumber", "getJigasiPin"], $this->functionNames($extension));
    }

    public function testGetJigasiNumberReturnsNumbersFromServerConfiguration(): void
    {
        self::bootKernel();
        $extension = $this->extension();
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 0']);
        $room->getServer()->setJigasiNumberUrl(
            '{"message":"numbers","numbers":{"DE":["0123456789"],"FR":["1234560123456789"]}}'
        );

        $result = $extension->getJigasiNumber($room);

        $this->assertSame(['0123456789'], $result['DE']);
        $this->assertSame(['1234560123456789'], $result['FR']);
    }

    public function testGetJigasiNumberReturnsNullWithoutRoomAndForInvalidConfiguration(): void
    {
        self::bootKernel();
        $extension = $this->extension();

        $this->assertNull($extension->getJigasiNumber(null));

        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 0']);
        $room->getServer()->setJigasiNumberUrl('https://invalid.url');

        $this->assertNull($extension->getJigasiNumber($room));
    }

    public function testGetJigasiPinFetchesPinFromApi(): void
    {
        self::bootKernel();
        $extension = $this->extension();
        $room = self::getContainer()->get(RoomsRepository::class)->findOneBy(['name' => 'TestMeeting: 0']);
        $room->getServer()
            ->setJigasiApiUrl('https://jigasi.org/conferenceMapper')
            ->setJigasiProsodyDomain('conference.jigasi.org');
        $jigasiService = self::getContainer()->get(JigasiService::class);
        $jigasiService->setClient(new MockHttpClient(
            static fn() => new MockResponse('{"conference":"test@conference.domain","id":154428,"message":"Successfully retrieved conference mapping"}')
        ));

        $this->assertSame('154428', $extension->getJigasiPin($room));
    }

    public function testGetJigasiPinReturnsNullWithoutRoom(): void
    {
        self::bootKernel();
        $extension = $this->extension();

        $this->assertNull($extension->getJigasiPin(null));
    }
}
