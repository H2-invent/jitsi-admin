<?php

namespace App\Tests\Utils;

use App\Service\CreateHttpsUrl;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ReplaceSchemaInURLTest extends KernelTestCase
{
    public function testReplacehttp(): void
    {
        $kernel = self::bootKernel();

        $sut = self::getContainer()->get(CreateHttpsUrl::class);
        $parameterBagMock = $this->createMock(ParameterBagInterface::class);
        $parameterBagMock
            ->expects(self::once())
            ->method('get')
            ->willReturn('https://baseurl.org/');
        $sut->setParamterBag(paramterBag: $parameterBagMock);
        self::assertEquals('https://testdomain.org',$sut->replaceSchemeOfAbsolutUrl('http://testdomain.org'));

    }
    public function testReplacehttps(): void
    {
        $kernel = self::bootKernel();

        $sut = self::getContainer()->get(CreateHttpsUrl::class);
        $parameterBagMock = $this->createMock(ParameterBagInterface::class);
        $parameterBagMock
            ->expects(self::once())
            ->method('get')
            ->willReturn('http://baseurl.org/');
        $sut->setParamterBag(paramterBag: $parameterBagMock);
        self::assertEquals('http://testdomain.org',$sut->replaceSchemeOfAbsolutUrl('https://testdomain.org'));

    }
    public function testReplaceftp(): void
    {
        $kernel = self::bootKernel();

        $sut = self::getContainer()->get(CreateHttpsUrl::class);
        $parameterBagMock = $this->createMock(ParameterBagInterface::class);
        $parameterBagMock
            ->expects(self::once())
            ->method('get')
            ->willReturn('ftp://baseurl.org/');
        $sut->setParamterBag(paramterBag: $parameterBagMock);
        self::assertEquals('ftp://testdomain.org',$sut->replaceSchemeOfAbsolutUrl('https://testdomain.org'));

    }
    public function testReplacehttpsInvalidUrl(): void
    {
        $kernel = self::bootKernel();

        $sut = self::getContainer()->get(CreateHttpsUrl::class);
        $parameterBagMock = $this->createMock(ParameterBagInterface::class);
        $parameterBagMock
            ->expects(self::once())
            ->method('get')
            ->willReturn('htsdffd stp::///sdfbaseurl.osdfrg/');
        $sut->setParamterBag(paramterBag: $parameterBagMock);
        self::assertEquals('https://testdomain.org',$sut->replaceSchemeOfAbsolutUrl('https://testdomain.org'));

    }
}
