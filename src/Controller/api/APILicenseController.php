<?php

namespace App\Controller\api;

use App\Entity\License;
use App\Helper\JitsiAdminController;
use App\Service\LicenseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class APILicenseController extends JitsiAdminController
{
    /**
     * @Route("/api/v1/generateLicense", name="api_generate_license",methods={"POST"})
     */
    public function index(Request $request, LicenseService $licenseService): Response
    {
        return new JsonResponse($licenseService->generateNewLicense(
            $request->get('license')
        )
        );
    }
}
