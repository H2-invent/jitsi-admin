<?php

namespace App\Controller;

use App\Entity\Server;
use App\Helper\JitsiAdminController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MoreFeaturesController extends JitsiAdminController
{
    #[\Symfony\Component\Routing\Attribute\Route(path: '/room/features/more', name: 'more_features', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $server = $this->doctrine->getRepository(Server::class)->find($request->get('id'));
        return new JsonResponse([
            'feature' => [
                'enableFeateureJwt' => $server->getFeatureEnableByJWT() ? true : false,
                'isE2EEEnabled'     => !$server->isEnforceE2e(),
            ]
        ]);
    }
}
