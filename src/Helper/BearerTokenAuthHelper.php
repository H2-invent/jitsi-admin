<?php
declare(strict_types=1);

namespace App\Helper;

use Symfony\Component\HttpFoundation\Request;

class BearerTokenAuthHelper
{
    /**
     * @note Matches this format: "Bearer [Any non whitespace Character]"
     */
    private const BEARER_TOKEN_REGEX = '/^Bearer (?<token>\S+)$/';

    public function getBearerTokenFromRequest(Request $request): ?string
    {
        $authorizationHeader = $request->headers->get('Authorization');
        if ($authorizationHeader === null) {
            return null;
        }
        preg_match(self::BEARER_TOKEN_REGEX, $authorizationHeader, $matches);

        return $matches['token'] ?? null;
    }
}