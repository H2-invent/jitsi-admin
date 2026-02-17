<?php
declare(strict_types=1);

namespace App\Tests\Provisioner;

use App\Service\RsaEncryptionService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RsaEncryptionServiceTest extends KernelTestCase
{
    private string $decryptedString = 'test_string';
    private string $encryptedString = 'iBGSykG49eJNkyLtUSYbBMdSkWZtWXBRQdUwOs6++tSp4gA0Hz/dy7gDv7QGA1q3Fo+suesI3ScHp5/3t8+tCpEXbtLEwBnVBrYis/iDM54xiMSsdPCCpL0nTzhTn3+tGHttzh+19NYVF7Gasyiz9AoVY6iQtphU2+apxc7rSxV/1crVmsBuyKAy2s+je1ashQxdSJEzbs+kKgb8vLQj2J/m2kIPPeH/vGRkMfi4Qyptc7Xs7anmktBog5gKA6WmxEXzbyre3IBjPZU6kp6w2Q2hVLYPLkfBvwafF00VQWFYwbCjh9ZlVPRuMTPXxCqgqMxz5sYDzq4wL4ME6/dvMg==';

    public function testEncryptionDecryptionRoundTrip(): void
    {
        self::bootKernel();
        $encryptionService = self::getContainer()->get(RsaEncryptionService::class);

        $encryptedString = $encryptionService->encryptBase64Wrapped($this->decryptedString);
        $decryptedString = $encryptionService->decryptBase64Wrapped($encryptedString);

        self::assertSame($this->decryptedString, $decryptedString);
        self::assertNotSame($this->decryptedString, $encryptedString);
    }

    public function testDecryption(): void
    {
        self::bootKernel();
        $encryptionService = self::getContainer()->get(RsaEncryptionService::class);

        $decryptedString = $encryptionService->decryptBase64Wrapped($this->encryptedString);

        self::assertSame($this->decryptedString, $decryptedString);
    }
}
