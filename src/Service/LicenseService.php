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
    private $notification;
    private $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag, NotificationService $notificationService, EntityManagerInterface $entityManager, TranslatorInterface $translator)
    {
        $this->translator = $translator;
        $this->em = $entityManager;
        $this->notification = $notificationService;
        $this->parameterBag = $parameterBag;
    }

    function verify(Server $server):bool
    {
        $license = $this->em->getRepository(License::class)->findOneBy(array('licenseKey'=>$server->getLicenseKey()));

        if(!$license){
            return false;
        }
        $data = json_decode($license->getLicense(),true);
        $signature = $data['signature'];
        $licenseString = $data['entry'];
        try {
            $res = openssl_verify(json_encode($licenseString), hex2bin($signature), file_get_contents($this->parameterBag->get('kernel.project_dir') . DIRECTORY_SEPARATOR  . 'key.pub'), OPENSSL_ALGO_SHA256);
            if($res!= 1){

                return false;
            }
        } catch (\Exception $e) {

            return false;
       }
        if(new \DateTime($licenseString['valid_until'])<new \DateTime()){

            return false;
        }
        if($server->getUrl() != $licenseString['server_url']){

            return false;
        }
        if($server->getLicenseKey() != $licenseString['license_key']){

            return false;
        }
        return true;
    }

}
