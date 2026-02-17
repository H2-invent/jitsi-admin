<?php
declare(strict_types=1);

namespace App\Tests\Provisioner;

use App\Message\ProvisionerStatus\Status;
use App\Message\ProvisionerStatusMessage;
use App\Message\Serializer\ProvisionerStatusSerializer;
use LogicException;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Envelope;

class ProvisionerStatusSerializerTest extends KernelTestCase
{
    private array $statusPayload = [
        'body' =>
            '{"room_id":"6bc5bd4df9dae5a3c0ebdb4244bdaf6b","status":"ready","name":"name","app_id":"app_id","app_secret": null,"url":"url"}'
    ];

    public function testDecode(): void
    {
        self::bootKernel();
        /** @var ProvisionerStatusSerializer $serializer */
        $serializer = self::getContainer()->get(ProvisionerStatusSerializer::class);

        $envelope = $serializer->decode($this->statusPayload);
        /** @var ProvisionerStatusMessage $message */
        $message = $envelope->getMessage();

        self::assertSame(ProvisionerStatusMessage::class, get_class($message));
        self::assertSame('6bc5bd4df9dae5a3c0ebdb4244bdaf6b', $message->room_id);
        self::assertSame(Status::READY, $message->status);
        self::assertSame('name', $message->name);
        self::assertSame('app_id', $message->app_id);
        self::assertNull($message->app_secret);
        self::assertSame('url', $message->url);
    }

    public function testEncode(): void
    {
        self::bootKernel();
        /** @var ProvisionerStatusSerializer $serializer */
        $serializer = self::getContainer()->get(ProvisionerStatusSerializer::class);

        $this->expectException(LogicException::class);
        $serializer->encode(new Envelope(new stdClass()));
    }
}
