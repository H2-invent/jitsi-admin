<?php

namespace App\Service\api;

use App\Entity\Server;
use App\Helper\BearerTokenAuthHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAuthorizationService
{
    public function __construct(
        private BearerTokenAuthHelper $bearerTokenAuthHelper,
        private LoggerInterface       $logger,
    )
    {
    }

    /**
     * Authorize against the server API key, matching the pattern used by other API controllers.
     * The legacy global SIP_CALLER_SECRET is still accepted as a fallback for existing Asterisk setups,
     * but it is deprecated and will be removed.
     */
    public function checkServerAuthorization(Request $request, ?Server $server, ?string $legacyToken = null): ?Response
    {
        $apiKey = $this->bearerTokenAuthHelper->getBearerTokenFromRequest($request);

        if ($apiKey === null) {
            return new JsonResponse(['authorized' => false], 401);
        }

        if ($server?->getApiKey() && $apiKey === $server->getApiKey()) {
            return null;
        }

        if ($legacyToken && $apiKey === $legacyToken) {
            $this->logger->warning(
                'Deprecated: the global SIP_CALLER_SECRET was used to authorize a caller api request. Configure the api key of the server instead.',
                ['path' => $request->getPathInfo()]
            );
            return null;
        }

        return new JsonResponse(['authorized' => false], 401);
    }

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
