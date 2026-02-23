<?php
declare(strict_types=1);

namespace App\Tests\Provisioner;

use App\Message\ProvisionerStatus\Status;
use App\Message\ProvisionerStatusMessage;
use App\Message\Serializer\ProvisionerStatusSerializer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Envelope;

class ProvisionerStatusSerializerTest extends KernelTestCase
{
    private const STATUS_ROOM_ID = '6bc5bd4df9dae5a3c0ebdb4244bdaf6b';
    private const STATUS_STATUS = Status::READY;
    private const STATUS_NAME = 'name';
    private const STATUS_APP_ID = 'app_id';
    private const STATUS_APP_SECRET = null;
    private const STATUS_URL = 'url';
    private const STATUS_PAYLOAD = [
        'body' => '{"room_id":"6bc5bd4df9dae5a3c0ebdb4244bdaf6b","status":"ready","name":"name","app_id":"app_id","app_secret":null,"url":"url"}',
        'headers' => [
            'Content-Type' => 'application/json'
        ],
    ];

    public function testDecode(): void
    {
        self::bootKernel();
        /** @var ProvisionerStatusSerializer $serializer */
        $serializer = self::getContainer()->get(ProvisionerStatusSerializer::class);

        $envelope = $serializer->decode(self::STATUS_PAYLOAD);
        /** @var ProvisionerStatusMessage $message */
        $message = $envelope->getMessage();

        self::assertSame(ProvisionerStatusMessage::class, get_class($message));
        self::assertSame(self::STATUS_ROOM_ID, $message->room_id);
        self::assertSame(self::STATUS_STATUS, $message->status);
        self::assertSame(self::STATUS_NAME, $message->name);
        self::assertSame(self::STATUS_APP_ID, $message->app_id);
        self::assertSame(self::STATUS_APP_SECRET, $message->app_secret);
        self::assertSame(self::STATUS_URL, $message->url);
    }

    public function testEncode(): void
    {
        self::bootKernel();
        /** @var ProvisionerStatusSerializer $serializer */
        $serializer = self::getContainer()->get(ProvisionerStatusSerializer::class);

        $envelope = new Envelope(
            new ProvisionerStatusMessage(
                self::STATUS_ROOM_ID,
                self::STATUS_STATUS,
                self::STATUS_NAME,
                self::STATUS_APP_ID,
                self::STATUS_APP_SECRET,
                self::STATUS_URL,
            )
        );

        $encodedMessage = $serializer->encode($envelope);

        self::assertSame(self::STATUS_PAYLOAD, $encodedMessage);
    }
}
