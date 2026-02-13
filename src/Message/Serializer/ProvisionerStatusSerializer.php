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

        $secret = $data['app_secret'];
        if ($secret !== null) {
            $secret = base64_decode($secret);
            $secret = $this->encryptionService->decrypt($secret);
        }

        return new Envelope(
            new ProvisionerStatusMessage(
                $data['room_id'],
                Status::from($data['status']),
                $data['name'],
                $data['app_id'],
                $secret,
                $data['url'],
            )
        );
    }

    public function encode(Envelope $envelope): array
    {
//        throw new LogicException("We should not write ProvisionerStatus Messages ourselves, always from Provisioner");

        //FIXME noch austauschen
        /** @var ProvisionerStatusMessage $message */
        $message = $envelope->getMessage();
        $message->appSecret = base64_encode($this->encryptionService->encrypt($message->appSecret ?? ''));

        return [
            'body' => json_encode($message, flags: JSON_THROW_ON_ERROR),
            'headers' => ['Content-Type' => 'application/json'],
        ];
    }
}
