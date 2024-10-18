<?php

namespace App\Tests\JitsiComponentSelector;

use App\Controller\JitsiComponentSelectorPublicKeyController;
use App\Service\caller\JitsiComponentSelectorService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use function PHPUnit\Framework\assertEquals;

class JitsiComponentSelectorControllerTest extends WebTestCase
{
    public function testGetPublicKey(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/signal/d1c8dfc1830cc0985d98acb9c6606ccb191ffdeb5c2be295c446dcea80391620.pem');
        self::assertEquals(
            '-----BEGIN PUBLIC KEY-----MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAi4v3alYXYIdsYjQHJF6Yjl82W0N4QMAoGqpBAg4j+HnNUFnULQw5MeFutSDu4gWWhix63gdGeB5JgGFhVkpq+57EBYeY1uPNSnCARDlKypM7yy3rMuDYQOnTQXAlFWca/LIRpgy2kkSpdWsVV8F9OMDQV7Pcx5cg5YygRsS/2ICVs+5i/9Lja1Jp0u6tcP+j+s9aNU6FO+atgwAkN/Wx7REFC/gS3GHO8JXmymTdH4ASAMiryxp9BrxWAWg5DCGiWVobfDV4NW2eSb6tU9spooRyTU4+L5Mb3tC81P7e8FFb78lFQW8tRN/D5Yw/Zroi+7/iGySp3HWtl/+TC+UCutJbQ+ADibJKm23eg9O8Qk8b02H2AvhiTzzOKZ2RdEaSbOzjGgm4k+2XkoU3uPqeIe57+25hZ+XbMBvxSSRX5b5nRZ8Qgf/Ay8/gyYe8Smczs5SvlON/3ZGglo2nXhyf0gdiQuDSCtxmcAJGNuYahUj3/thAm7GhJf3c0KnaLVu6h9ZZpyQS6Hyi+TSopBC2vGvKICi29jc5CnMSm39N+CBBfS6as5LvVAcstEltd4qNAKV9+B6SMwmP8WRfinTM6X1rHWpLT9rCPvzhri1XcqRjmiFwUmX11C3sHPrNFKT6gBUOeC0GhCt8k6q2reptkMj/WRoXh73KCNmYGOqs95kCAwEAAQ==-----END PUBLIC KEY-----',
            preg_replace('/[\r\n]+/', '', $client->getResponse()->getContent())
        );
        self::assertEquals(200,$client->getResponse()->getStatusCode());
    }

    public function testGetPublicKeyError(): void
    {
        $client = static::createClient();

        $componentSelectorController = self::getContainer()->get(JitsiComponentSelectorPublicKeyController::class);
        $componentSelectorController->setPublicKeyPath('invalidPath');
        $crawler = $client->request('GET', '/signal/d1c8dfc1830cc0985d98acb9c6606ccb191ffdeb5c2be295c446dcea80391620.pem');
        self::assertEquals(404,$client->getResponse()->getStatusCode());
    }
}
