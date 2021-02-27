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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use Symfony\Contracts\Translation\TranslatorInterface;


class LicenseService
{


    private $em;
    private $translator;
    private $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag, EntityManagerInterface $entityManager, TranslatorInterface $translator)
    {
        $this->translator = $translator;
        $this->em = $entityManager;
        $this->parameterBag = $parameterBag;
    }

    function verify(Server $server): bool
    {
        $license = $this->em->getRepository(License::class)->findOneBy(array('licenseKey' => $server->getLicenseKey()));

        if (!$license) {
            return false;
        }
        if(!$this->verifySignature($license->getLicense())){
            return false;
        }
        $data = json_decode($license->getLicense(), true);
        $signature = $data['signature'];
        $licenseString = $data['entry'];
        try {
            $res = openssl_verify(json_encode($licenseString), hex2bin($signature), file_get_contents($this->parameterBag->get('kernel.project_dir') . DIRECTORY_SEPARATOR . 'key.pub'), OPENSSL_ALGO_SHA256);
            if ($res != 1) {

                return false;
            }
        } catch (\Exception $e) {

            return false;
        }
        if (new \DateTime($licenseString['valid_until']) < new \DateTime()) {

            return false;
        }
        if ($server->getUrl() != $licenseString['server_url']) {

            return false;
        }
        if ($server->getLicenseKey() != $licenseString['license_key']) {

            return false;
        }
        return true;
    }

    public function verifySignature($inputString)
    {
        $data = json_decode($inputString, true);
        $signature = $data['signature'];
        $licenseString = $data['entry'];
        try {
            $res = openssl_verify(json_encode($licenseString), hex2bin($signature), file_get_contents($this->parameterBag->get('kernel.project_dir') . DIRECTORY_SEPARATOR . 'key.pub'), OPENSSL_ALGO_SHA256);
            if ($res != 1) {

                return false;
            }
        } catch (\Exception $e) {

            return false;
        }
        return true;
    }
    public function generateNewLicense($licenseString){
        if (!$this->verifySignature($licenseString)) {
            return array('error' => true, 'text' => 'Invalid Signature');
        }

        $data = json_decode($licenseString, true);
        $licenseArr = $data['entry'];
        $license =$this->em->getRepository(License::class)->findOneBy(array('licenseKey'=>$licenseArr['license_key']));
        if($license){
            return array('error' => true, 'text' => 'Licensekey already added');
        }

        $license = new License();
        $license->setUrl($licenseArr['server_url']);
        $license->setValidUntil((new \DateTime($licenseArr['valid_until']))->setTime(23, 59, 59));
        $license->setLicenseKey($licenseArr['license_key']);
        $license->setLicense($licenseString);

        $this->em->persist($license);
        $this->em->flush();

        return array('error' => false, 'licenseKey' => $license->getLicenseKey());
    }
    public function validUntil(Server $server){
        $license= $this->em->getRepository(License::class)->findOneBy(array('licenseKey' => $server->getLicenseKey()));
        return $license->getValidUntil();
    }
}
