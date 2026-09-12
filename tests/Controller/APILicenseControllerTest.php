<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class APILicenseControllerTest extends WebTestCase
{
    public function testIndexWithInvalidLicense(): void
    {
        $client = static::createClient();
        $license = json_encode([
            'signature' => 'aabbcc',
            'entry' => [
                'valid_until' => '2030-01-01',
                'server_url' => 'meet.example.com',
                'license_key' => 'someKey',
            ],
        ]);
        $client->request('POST', '/api/v1/generateLicense', ['license' => $license]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['error']);
        $this->assertSame('Invalid Signature', $data['text']);
    }
}
