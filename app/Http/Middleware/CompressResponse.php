<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Sends pages gzip-compressed when the web server is not already doing it.
 * A storefront page is mostly repeated markup, so it travels at roughly a seventh of its size:
 * the single biggest saving for a phone on a slow connection.
 */
class CompressResponse
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (! $response instanceof Response
            || ! function_exists('gzencode')
            || ini_get('zlib.output_compression')
            || $response->headers->has('Content-Encoding')
            || ! str_contains((string) $request->header('Accept-Encoding'), 'gzip')) {
            return $response;
        }

        $content = $response->getContent();
        $type = (string) $response->headers->get('Content-Type', 'text/html');
        if (! is_string($content) || strlen($content) < 2048 || ! preg_match('~^(text/|application/(json|xml|javascript))~i', $type)) {
            return $response;
        }

        $packed = gzencode($content, 6);
        if ($packed === false) {
            return $response;
        }

        $response->setContent($packed);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Vary', 'Accept-Encoding', false);
        $response->headers->set('Content-Length', (string) strlen($packed));

        return $response;
    }
}
