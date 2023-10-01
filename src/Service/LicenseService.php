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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class LicenseService
{
    private $em;
    private $translator;
    private $parameterBag;
    private CheckSignature $checkSignature;
    public function __construct(CheckSignature $checkSignature, ParameterBagInterface $parameterBag, EntityManagerInterface $entityManager, TranslatorInterface $translator)
    {
        $this->translator = $translator;
        $this->em = $entityManager;
        $this->parameterBag = $parameterBag;
        $this->checkSignature = $checkSignature;
    }

    function verify(?Server $server): bool
    {
        return true;
    }


    public function generateNewLicense($licenseString)
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
        $license->setValidUntil((new \DateTime($licenseArr['valid_until']))->setTime(23, 59, 59));
        $license->setLicenseKey($licenseArr['license_key']);
        $license->setLicense($licenseString);

        $this->em->persist($license);
        $this->em->flush();

        return ['error' => false, 'licenseKey' => $license->getLicenseKey()];
    }

    public function validUntil(Server $server)
    {
        $license = $this->em->getRepository(License::class)->findOneBy(['licenseKey' => $server->getLicenseKey()]);
        return $license->getValidUntil();
    }
}
