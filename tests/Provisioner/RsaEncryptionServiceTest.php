<?php
declare(strict_types=1);

namespace App\Tests\Provisioner;

use App\Service\RsaEncryptionService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RsaEncryptionServiceTest extends KernelTestCase
{
    private string $decryptedString = 'test_string';
    public function testEncryptionDecryptionRoundTrip(): void
    {
        self::bootKernel();
        $encryptionService = self::getContainer()->get(RsaEncryptionService::class);

        $encryptedString = $encryptionService->encryptBase64Wrapped($this->decryptedString);
        $decryptedString = $encryptionService->decryptBase64Wrapped($encryptedString);

        self::assertSame($this->decryptedString, $decryptedString);
        self::assertNotSame($this->decryptedString, $encryptedString);
    }
}
