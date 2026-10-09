<?php
declare(strict_types=1);

namespace App\Tests\Provisioner;

use App\Service\RsaEncryptionService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RsaEncryptionServiceTest extends KernelTestCase
{
    private string $decryptedString = 'test_string';
    private string $encryptedString = 'YNKJGoGSq+GLJa/oDgTRlPjyyTIDJdJUc9q4D9y/E3vKomoC5dJsTrLK1Y1x2Yt/k8ETv3rcCL5wnn14xsu+LxT/RKE6pEXPr0hXSFFFh3SEYL94gttubstCUEVw7A5xE8U4vZbuyM6noPPi7FAm3fVTDRTwg4OcuPpQUOcjI4p4OMu6b+6Xzbv/aOGoJ/8/0JMlYvuachX4kYu+tTwkX758GcIBUcLAgeoxxY0UoN3vsyOOspeGtiBO2UoQ0luUC4Ax9cg+fGMAclSFXlDLChcOLG2XqxgjuPJMdl4sfRtC51vGdV5qqt1SKcSISVAw7wHDSpX3w9cwMyUEoAKChA==';

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
