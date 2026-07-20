<?php
declare(strict_types=1);

namespace App\Service;

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\PrivateKey;
use phpseclib3\Crypt\RSA\PublicKey;

class RsaEncryptionService
{
    private const HASH = 'SHA256';
    private const PADDING = RSA::ENCRYPTION_OAEP;

    private ?PrivateKey $privateKey = null;
    private ?PublicKey $publicKey = null;

    /**
     * $publicKeyPath is nullable because as of now, we don't use public key encryption, only private key decryption
     * also we wrap everything in base64 to be able to transmit these strings via JSON
     */
    public function __construct(
        private readonly string $privateKeyPath,
        private readonly ?string $publicKeyPath = null,
    )
    {
    }

    public function decryptBase64Wrapped(string $data): string
    {
        if ($this->privateKey === null) {
            $this->initPrivateKey();
        }

        $decoded = base64_decode($data, true);
        if ($decoded === false) {
            throw new \RuntimeException('Could not base64 decode string');
        }

        try {
            $decrypted = $this->privateKey->decrypt($decoded);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Could not decrypt string', previous: $e);
        }

        return $decrypted;
    }

    public function encryptBase64Wrapped(string $data): string
    {
        if ($this->publicKey === null) {
            $this->initPublicKey();
        }

        try {
            $encrypted = $this->publicKey->encrypt($data);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Could not encrypt string', 0, $e);
        }

        return base64_encode($encrypted);
    }

    public function initPrivateKey(): void
    {
        $privateKeyContent = file_get_contents($this->privateKeyPath);
        if ($privateKeyContent === false) {
            throw new \RuntimeException('Could not find and open private key');
        }

        try {
            /** @var PrivateKey $privateKey */
            $privateKey = PublicKeyLoader::loadPrivateKey($privateKeyContent);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Could not parse private key content', previous: $e);
        }

        $this->privateKey = $privateKey
            ->withPadding(self::PADDING)
            ->withHash(self::HASH)
            ->withMGFHash(self::HASH);
    }

    public function initPublicKey(): void
    {
        if ($this->publicKeyPath === null) {
            throw new \RuntimeException('Public key path is required for encryption');
        }

        $publicKeyContent = file_get_contents($this->publicKeyPath);
        if ($publicKeyContent === false) {
            throw new \RuntimeException('Could not find and open public key');
        }

        try {
            /** @var PublicKey $publicKey */
            $publicKey = PublicKeyLoader::loadPublicKey($publicKeyContent);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Could not parse public key content', previous: $e);
        }

        $this->publicKey = $publicKey
            ->withPadding(self::PADDING)
            ->withHash(self::HASH)
            ->withMGFHash(self::HASH);
    }
}
