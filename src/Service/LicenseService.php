<?php

/**
 * Created by PhpStorm.
 * User: Emanuel
 * Date: 03.10.2019
 * Time: 19:01
 */

namespace App\Service;

use App\Entity\License;
use App\Entity\Server;
use Doctrine\ORM\EntityManagerInterface;
use H2Entwicklung\Signature\CheckSignature;

class LicenseService
{
    private EntityManagerInterface $em;
    private CheckSignature $checkSignature;
    public function __construct(CheckSignature $checkSignature, EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
        $this->checkSignature = $checkSignature;
    }

    function verify(?Server $server): bool
    {
        return true;
    }


    /**
     * @return array{error: bool, text?: string, licenseKey?: string}
     */
    public function generateNewLicense(string $licenseString): array
    {
        if (!$this->checkSignature->verifySignature($licenseString)) {
            return ['error' => true, 'text' => 'Invalid Signature'];
        }

        $data = json_decode($licenseString, true);
        $licenseArr = $data['entry'];
        $license = $this->em->getRepository(License::class)->findOneBy(['licenseKey' => $licenseArr['license_key']]);
        if ($license) {
            return ['error' => true, 'text' => 'Licensekey already added'];
        }

        $license = new License();
        $license->setUrl($licenseArr['server_url']);
        $license->setValidUntil((new \DateTimeImmutable($licenseArr['valid_until']))->setTime(23, 59, 59));
        $license->setLicenseKey($licenseArr['license_key']);
        $license->setLicense($licenseString);

        $this->em->persist($license);
        $this->em->flush();

        /** @var string $licenseKey */
        $licenseKey = $license->getLicenseKey();

        return ['error' => false, 'licenseKey' => $licenseKey];
    }

    public function validUntil(Server $server): ?\DateTimeImmutable
    {
        $license = $this->em->getRepository(License::class)->findOneBy(['licenseKey' => $server->getLicenseKey()]);
        return $license->getValidUntil();
    }
}
