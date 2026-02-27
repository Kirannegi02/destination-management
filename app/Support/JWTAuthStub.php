<?php

namespace Tymon\JWTAuth\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Stub facade for JWTAuth when tymon/jwt-auth package is not installed.
 * This file is only used as a fallback when the package is missing.
 */
class JWTAuth extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        // Return a dummy accessor - this won't be resolved when package is not installed
        return 'tymon.jwt.auth';
    }

    /**
     * Handle dynamic static method calls.
     * This ensures our static methods are called directly without facade resolution.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public static function __callStatic($method, $args)
    {
        // If method exists as static method, call it directly
        if (method_exists(static::class, $method)) {
            return static::$method(...$args);
        }
        
        // Otherwise, try parent facade resolution (will fail gracefully if service not registered)
        try {
            return parent::__callStatic($method, $args);
        } catch (\Exception $e) {
            throw new \BadMethodCallException("Method {$method} does not exist on " . static::class);
        }
    }

    /**
     * Create a token from a user object.
     * 
     * @param mixed $user
     * @return string
     */
    public static function fromUser($user)
    {
        // Generate a simple token when package is not installed
        // This is a basic implementation - in production, install the package for proper JWT tokens
        $header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256'])));
        $payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode([
            'sub' => $user->id ?? $user->getKey(),
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24), // 24 hours
        ])));
        $signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(hash_hmac('sha256', "$header.$payload", config('app.key', 'fallback-secret-key'), true)));
        
        return "$header.$payload.$signature";
    }

    /**
     * Get the token from the request.
     * 
     * @return string|null
     */
    public static function getToken()
    {
        $request = request();
        
        // Try to get token from Authorization header
        $authHeader = $request->header('Authorization');
        if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        // Try to get token from query parameter
        return $request->query('token');
    }

    /**
     * Invalidate a token.
     * 
     * @param mixed $token
     * @return bool
     */
    public static function invalidate($token)
    {
        // Stub implementation - in production, install the package for proper token invalidation
        return true;
    }

    /**
     * Get the authenticated user.
     * 
     * @return mixed
     */
    public static function user()
    {
        return auth('api')->user();
    }
}

