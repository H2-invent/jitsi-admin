<?php
declare(strict_types=1);

namespace App\Message\Serializer;

use App\Message\ProvisionerStatus\Status;
use App\Message\ProvisionerStatusMessage;
use App\Service\RsaEncryptionService;
use LogicException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class ProvisionerStatusSerializer implements SerializerInterface
{
    public function __construct(
        private readonly RsaEncryptionService $encryptionService,
    )
    {
    }

    public function decode(array $encodedEnvelope): Envelope
    {
        $body = $encodedEnvelope['body'];
        $data = json_decode($body, true, flags: JSON_THROW_ON_ERROR);

        $secret = $data['app_secret'] ?? null;
        if ($secret !== null) {
            $secret = $this->encryptionService->decryptBase64Wrapped($secret);
        }

        return new Envelope(
            new ProvisionerStatusMessage(
                $data['room_id'],
                Status::from($data['status']),
                $data['name'] ?? null,
                $data['app_id'] ?? null,
                $secret,
                $data['url'] ?? null,
            )
        );
    }

    public function encode(Envelope $envelope): array
    {
        /** @var ProvisionerStatusMessage $message */
        $message = $envelope->getMessage();
        $message->app_secret = $this->encryptionService->encryptBase64Wrapped($message->appSecret ?? '');

        return [
            'body' => json_encode($message, flags: JSON_THROW_ON_ERROR),
            'headers' => ['Content-Type' => 'application/json'],
        ];
    }
}
