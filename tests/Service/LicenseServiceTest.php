<?php

namespace App\Tests\Service;

use App\Entity\License;
use App\Entity\Server;
use App\Repository\LicenseRepository;
use App\Service\LicenseService;
use Doctrine\ORM\EntityManagerInterface;
use H2Entwicklung\Signature\CheckSignature;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class LicenseServiceTest extends KernelTestCase
{
    public function testVerifyIsAnUnimplementedStub(): void
    {
        self::bootKernel();
        $service = self::getContainer()->get(LicenseService::class);

        self::assertTrue($service->verify(null));
        self::markTestIncomplete('LicenseService::verify() is an unimplemented stub that always returns true; actual license enforcement is not performed.');
    }

    public function testGenerateNewLicenseRejectsInvalidSignature(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $checkSignature = $this->createMock(CheckSignature::class);
        $checkSignature->expects($this->once())->method('verifySignature')->willReturn(false);

        $service = new LicenseService(
            $checkSignature,
            $container->get(ParameterBagInterface::class),
            $container->get(EntityManagerInterface::class),
            $container->get(TranslatorInterface::class)
        );

        $result = $service->generateNewLicense('not-a-signed-license');

        self::assertSame(['error' => true, 'text' => 'Invalid Signature'], $result);
    }

    public function testGenerateNewLicenseStoresLicenseWithValidSignature(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $licenseKey = 'test-license-' . uniqid();
        $licenseString = json_encode([
            'entry' => [
                'license_key' => $licenseKey,
                'server_url' => 'https://license.example.org',
                'valid_until' => '2030-01-02',
            ],
        ]);

        $checkSignature = $this->createMock(CheckSignature::class);
        $checkSignature->expects($this->once())->method('verifySignature')->with($licenseString)->willReturn(true);

        $service = new LicenseService(
            $checkSignature,
            $container->get(ParameterBagInterface::class),
            $container->get(EntityManagerInterface::class),
            $container->get(TranslatorInterface::class)
        );

        $result = $service->generateNewLicense($licenseString);

        self::assertSame(['error' => false, 'licenseKey' => $licenseKey], $result);

        $license = $container->get(LicenseRepository::class)->findOneBy(['licenseKey' => $licenseKey]);
        self::assertInstanceOf(License::class, $license);
        self::assertSame('https://license.example.org', $license->getUrl());
        self::assertSame($licenseString, $license->getLicense());
        self::assertSame('2030-01-02 23:59:59', $license->getValidUntil()->format('Y-m-d H:i:s'));
    }

    public function testGenerateNewLicenseRejectsDuplicateLicenseKey(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $licenseKey = 'duplicate-' . uniqid();
        $licenseString = json_encode([
            'entry' => [
                'license_key' => $licenseKey,
                'server_url' => 'https://license.example.org',
                'valid_until' => '2030-01-02',
            ],
        ]);

        $checkSignature = $this->createMock(CheckSignature::class);
        $checkSignature->method('verifySignature')->willReturn(true);

        $service = new LicenseService(
            $checkSignature,
            $container->get(ParameterBagInterface::class),
            $container->get(EntityManagerInterface::class),
            $container->get(TranslatorInterface::class)
        );

        self::assertSame(['error' => false, 'licenseKey' => $licenseKey], $service->generateNewLicense($licenseString));
        self::assertSame(['error' => true, 'text' => 'Licensekey already added'], $service->generateNewLicense($licenseString));
    }

    public function testValidUntilReturnsDateOfMatchingLicense(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $licenseKey = 'valid-until-' . uniqid();

        $license = new License();
        $license->setLicenseKey($licenseKey)
            ->setLicense('{"entry":{}}')
            ->setUrl('https://license.example.org')
            ->setValidUntil(new \DateTime('2031-05-06 23:59:59'));
        $em = $container->get(EntityManagerInterface::class);
        $em->persist($license);
        $em->flush();

        $server = new Server();
        $server->setLicenseKey($licenseKey);

        $service = $container->get(LicenseService::class);

        self::assertSame('2031-05-06 23:59:59', $service->validUntil($server)->format('Y-m-d H:i:s'));
    }
}
