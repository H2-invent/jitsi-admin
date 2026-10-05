<?php

namespace App\Service\Api;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAuthorizationService
{


    public static function checkHEader(Request $request, string $token): ?Response
    {
        $authHeader = $request->headers->get('Authorization');
        if ($authHeader !== $token) {
            $array = ['authorized' => false];
            $response = new JsonResponse($array, \Symfony\Component\HttpFoundation\Response::HTTP_UNAUTHORIZED);

            return $response;
        }

        return null;
    }
}
