<?php

namespace App\Service\api;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAuthorizationService
{


    public static function checkHEader(Request $request, $token): ?Response
    {
        $authHeader = $request->headers->get('Authorization');
        if ($authHeader !== $token) {
            $array = ['authorized' => false];
            $response = new JsonResponse($array, 401);

            return $response;
        }

        return null;
    }
}
