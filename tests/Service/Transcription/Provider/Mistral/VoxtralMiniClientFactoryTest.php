<?php
declare(strict_types=1);

namespace App\Tests\Service\Transcription\Provider\Mistral;

use App\Entity\Server;
use App\Service\Transcription\Provider\Mistral\VoxtralMiniClientFactory;
use Partitech\PhpMistral\Clients\Mistral\MistralClient;
use PHPUnit\Framework\TestCase;

class VoxtralMiniClientFactoryTest extends TestCase
{
    public function testCreateBuildsClientFromServerApiKeyAndConfiguredUri(): void
    {
        $factory = new VoxtralMiniClientFactory('https://mistral.example/v1');

        $server = (new Server())->setApiKeyTranscription('server-secret-key');

        $client = $factory->create($server);

        $this->assertInstanceOf(MistralClient::class, $client);

        $reflection = new \ReflectionClass($client);
        $apiKey = $reflection->getProperty('apiKey');
        $apiKey->setAccessible(true);
        $url = $reflection->getProperty('url');
        $url->setAccessible(true);

        $this->assertSame('server-secret-key', $apiKey->getValue($client));
        $this->assertSame('https://mistral.example/v1', $url->getValue($client));
    }

    public function testCreateReturnsDifferentClientsPerServer(): void
    {
        $factory = new VoxtralMiniClientFactory('https://mistral.example/v1');

        $first = $factory->create((new Server())->setApiKeyTranscription('first'));
        $second = $factory->create((new Server())->setApiKeyTranscription('second'));

        $this->assertNotSame($first, $second);

        $reflection = new \ReflectionClass($first);
        $apiKey = $reflection->getProperty('apiKey');
        $apiKey->setAccessible(true);

        $this->assertSame('first', $apiKey->getValue($first));
        $this->assertSame('second', $apiKey->getValue($second));
    }
}
