<?php

namespace App\Tests\Utils;

use App\Service\CreateHttpsUrl;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class HTTPSMaker extends KernelTestCase
{
    public function testCreatHttps(): void
    {
        $kernel = self::bootKernel();
        $createHttpsUrl = self::getContainer()->get(CreateHttpsUrl::class);
        $urlGen=self::getContainer()->get(UrlGeneratorInterface::class);
        self::assertEquals('http://localhost:8000/', $createHttpsUrl->createHttpsUrl('/'));
        self::assertEquals('http://localhost:8000/room/dashboard', $createHttpsUrl->createHttpsUrl('/room/dashboard'));
        self::assertEquals('https://localhost:8000/', $createHttpsUrl->generateAbsolutUrl('http://localhost:8000','/'));
        self::assertEquals('https://localhost:8000/room/dashboard', $createHttpsUrl->generateAbsolutUrl('http://localhost:8000','/room/dashboard'));
        self::assertEquals('http://localhost:8000/room/dashboard', $createHttpsUrl->createHttpsUrl($urlGen->generate('dashboard')));
        self::assertEquals('https://localhost:8000/room/dashboard', $createHttpsUrl->generateAbsolutUrl('http://localhost:8000',$urlGen->generate('dashboard')));
    }
}
