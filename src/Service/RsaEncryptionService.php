<?php
declare(strict_types=1);

namespace App\Service;

class RsaEncryptionService
{
    private const PADDING = OPENSSL_PKCS1_OAEP_PADDING;

    public function __construct(
        private readonly string $privateKeyPath,
        private readonly ?string $publicKeyPath = null,
    )
    {
    }

    public function decrypt(string $data): string
    {
        $privateKeyContent = file_get_contents($this->privateKeyPath);
        if ($privateKeyContent === false) {
            throw new \RuntimeException('Could not find and open private key');
        }

        $privateKey = openssl_pkey_get_private($privateKeyContent);
        if ($privateKey === false) {
            throw new \RuntimeException('Could not parse private key content');
        }

        $decrypted = null;
        $success = openssl_private_decrypt($data, $decrypted, $privateKey, self::PADDING);
        if (!$success || $decrypted === null) {
            throw new \RuntimeException('Could not decrypt string');
        }

        return $decrypted;
    }

    /**
     * this will probably not be used by prod, but is for testing purposes only
     */
    public function encrypt(string $data): string
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

        $encrypted = null;
        $success = openssl_public_encrypt($data, $encrypted, $publicKey, self::PADDING);
        if (!$success || $encrypted === null) {
            throw new \RuntimeException('Could not encrypt string');
        }

        return $encrypted;
    }
}
