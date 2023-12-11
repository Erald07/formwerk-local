<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\AccessToken;

class VerifyApiAccessToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!$request->hasHeader("X-Formwerk-Api-Token")) {
            return response("Application access token is required.", 403);
        }

        $requestAccessToken = $request->header("X-Formwerk-Api-Token");
        $requestDomain = $request->header("X-Formwerk-Api-Domain");
        $accessToken = AccessToken::firstWhere("token", $requestAccessToken);

        if (!$accessToken) {
            return response("Access token not correct.", 403);
        }
        if (!$requestDomain) {
            return response("X-Formwerk-Api-Domain header is missing.", 403);
        }
        $restrictedDomains = $accessToken->restrictedDomains;
        $isValid = false;
        foreach ($restrictedDomains as $restrictedDomain) {
            $domain = $restrictedDomain->domain;
            if ($restrictedDomain->deleted_at !== null) {
                continue;
            }

            if (strpos($domain, "*.") === 0) {
                if (fnmatch($domain, $requestDomain)) {
                    $isValid = true;
                    break;
                }
            } else {
                if ($domain === $requestDomain) {
                    $isValid = true;
                    break;
                }
            }
        }
        if (!$isValid) {
            return response("Your site is blacklisted", 403);
        }

        return $next($request);
    }
}
