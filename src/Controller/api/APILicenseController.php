<?php

namespace App\Controller\api;

use App\Entity\License;
use App\Service\LicenseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class APILicenseController extends AbstractController
{
    /**
     * @Route("/api/v1/generateLicense", name="api_generate_license",methods={"POST"})
     */
    public function index(Request $request, LicenseService $licenseService): Response
    {
        if (!$licenseService->verifySignature($request->get('license'))) {
            return new JsonResponse(array('error' => true, 'text' => 'Invalid Signature'));
        }
        $license = $this->getDoctrine()->getRepository(License::class)->findOneBy(array('licenseKey'=>$request->get('license_key')));
        if($license){
            return new JsonResponse(array('error' => true, 'text' => 'Licensekey already added'));
        }
        $license = new License();
        $license->setUrl($request->get('server_url'));
        $license->setValidUntil((new \DateTime($request->get('valid_until')))->setTime(23, 59, 59));
        $license->setLicenseKey($request->get('license_key'));
        $license->setLicense($request->get('license'));
        $em = $this->getDoctrine()->getManager();
        $em->persist($license);
        $em->flush();

        return new JsonResponse(array('error' => false, 'licenseKey' => $license->getLicenseKey()));
    }
}
