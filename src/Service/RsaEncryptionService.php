<?php
declare(strict_types=1);

namespace App\Service;

use OpenSSLAsymmetricKey;

class RsaEncryptionService
{
    private const PADDING = OPENSSL_PKCS1_OAEP_PADDING;

    private ?OpenSSLAsymmetricKey $privateKey = null;
    private ?OpenSSLAsymmetricKey $publicKey = null;

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

        $decoded = base64_decode($data);
        if ($decoded === false) {
            throw new \RuntimeException('Could not base64 decode string');
        }

        $decrypted = '';
        $success = openssl_private_decrypt($decoded, $decrypted, $this->privateKey, self::PADDING);
        if (!$success || $decrypted === null) {
            throw new \RuntimeException('Could not decrypt string');
        }

        return $decrypted;
    }

    public function encryptBase64Wrapped(string $data): string
    {
        if ($this->publicKey === null) {
            $this->initPublicKey();
        }

        $encrypted = null;
        $success = openssl_public_encrypt($data, $encrypted, $this->publicKey, self::PADDING);
        if (!$success || $encrypted === null) {
            throw new \RuntimeException('Could not encrypt string');
        }

        return base64_encode($encrypted);
    }

    public function initPrivateKey(): void
    {
        $privateKeyContent = file_get_contents($this->privateKeyPath);
        if ($privateKeyContent === false) {
            throw new \RuntimeException('Could not find and open private key');
        }

        $privateKey = openssl_pkey_get_private($privateKeyContent);
        if ($privateKey === false) {
            throw new \RuntimeException('Could not parse private key content');
        }

        $this->privateKey = $privateKey;
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

        $publicKey = openssl_pkey_get_public($publicKeyContent);
        if ($publicKey === false) {
            throw new \RuntimeException('Could not parse public key content');
        }

        $this->publicKey = $publicKey;
    }
}
