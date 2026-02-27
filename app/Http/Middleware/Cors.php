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
            $this->addHeaders($response, $request);
            return $response;
        }

        // Normal request
        $response = $next($request);
        $this->addHeaders($response, $request);

        return $response;
    }

    /**
     * Add CORS headers to all responses.
     */
    private function addHeaders(Response $response, Request $request): void
    {
        // Get the origin from the request
        $origin = $request->headers->get('Origin');
        
        // Get allowed origins from environment or use default
        $allowedOrigins = $this->getAllowedOrigins();
        
        // If origin is in allowed list, use it; otherwise use first allowed origin or allow all for development
        if ($origin && in_array($origin, $allowedOrigins)) {
            $allowedOrigin = $origin;
        } elseif (in_array('*', $allowedOrigins)) {
            // Allow all origins (for development only)
            $allowedOrigin = $origin ?: '*';
        } elseif (!empty($allowedOrigins)) {
            // Use first allowed origin as fallback
            $allowedOrigin = $allowedOrigins[0];
        } else {
            // Default fallback
            $allowedOrigin = $origin ?: '*';
        }

        $response->headers->set('Access-Control-Allow-Origin', $allowedOrigin);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN');
        $response->headers->set('Access-Control-Expose-Headers', 'Authorization, Content-Length, Content-Type');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Max-Age', '86400'); // 24 hours
        $response->headers->set('Vary', 'Origin');
    }

    /**
     * Get allowed origins from environment configuration.
     */
    private function getAllowedOrigins(): array
    {
        // Get from environment variable (comma-separated list)
        $envOrigins = env('CORS_ALLOWED_ORIGINS', '');
        
        if (!empty($envOrigins)) {
            return array_map('trim', explode(',', $envOrigins));
        }
        
        // Default allowed origins for common development ports
        return [
            'http://localhost:3000',
            'http://localhost:5173',
            'http://localhost:5174',
            'http://localhost:8080',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:5173',
            'http://127.0.0.1:5174',
            'http://127.0.0.1:8080',
        ];
    }
}
