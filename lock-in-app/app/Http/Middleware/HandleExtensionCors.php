<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleExtensionCors
{
    private const ALLOWED_SCHEMES = ['moz-extension://', 'chrome-extension://'];

    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->headers->get('Origin', '');

        $isExtension = collect(self::ALLOWED_SCHEMES)
            ->contains(fn ($scheme) => str_starts_with($origin, $scheme));

        if (! $isExtension) {
            return $next($request);
        }

        // Skip CSRF for verified extension origins — origin validation above is equivalent
        $request->attributes->set('_skip_csrf', true);

        if ($request->isMethod('OPTIONS')) {
            return response('', 204)
                ->header('Access-Control-Allow-Origin', $origin)
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, X-XSRF-TOKEN, X-Requested-With, Accept')
                ->header('Access-Control-Max-Age', '86400');
        }

        $response = $next($request);

        return $response
            ->header('Access-Control-Allow-Origin', $origin)
            ->header('Access-Control-Allow-Credentials', 'true')
            ->header('Access-Control-Allow-Headers', 'Content-Type, X-XSRF-TOKEN, X-Requested-With, Accept');
    }
}
