<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Cors
{
    /**
     * Handle an incoming request by adding CORS headers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ❗ Handle OPTIONS (preflight) BEFORE other middleware
        if ($request->isMethod('OPTIONS')) {
            $response = response('', 200);
            $this->addHeaders($response);
            return $response;
        }

        // Normal request
        $response = $next($request);
        $this->addHeaders($response);

        return $response;
    }

    /**
     * Add CORS headers to all responses.
     */
    private function addHeaders(Response $response): void
    {
        // 💡 Hard-code your frontend origin here
        // Change if your local port is different
        $allowedOrigin = 'http://localhost:5173';

        $response->headers->set('Access-Control-Allow-Origin', $allowedOrigin);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');
        $response->headers->set('Access-Control-Expose-Headers', 'Authorization, Content-Length, Content-Type');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Vary', 'Origin');
    }
}
