<?php

namespace App\Support;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use App\Models\User;

/**
 * Custom JWT Guard that works without the tymon/jwt-auth package.
 * This is a fallback implementation when the package is not installed.
 */
class JWTGuard implements Guard
{
    protected $provider;
    protected $request;
    protected $user;

    public function __construct(UserProvider $provider, Request $request)
    {
        $this->provider = $provider;
        $this->request = $request;
    }

    /**
     * Determine if the current user is authenticated.
     *
     * @return bool
     */
    public function check()
    {
        return !is_null($this->user());
    }

    /**
     * Determine if the current user is a guest.
     *
     * @return bool
     */
    public function guest()
    {
        return !$this->check();
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        if (!is_null($this->user)) {
            return $this->user;
        }

        $token = $this->getTokenFromRequest();

        if (!$token) {
            return null;
        }

        // Decode and validate the token
        $payload = $this->validateToken($token);

        if (!$payload) {
            return null;
        }

        // Get user ID from token
        $userId = $payload['sub'] ?? null;

        if (!$userId) {
            return null;
        }

        // Get user from provider
        $this->user = $this->provider->retrieveById($userId);

        return $this->user;
    }

    /**
     * Get the ID for the currently authenticated user.
     *
     * @return int|string|null
     */
    public function id()
    {
        $user = $this->user();
        return $user ? $user->getAuthIdentifier() : null;
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array  $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        // Not used for JWT
        return false;
    }

    /**
     * Set the current user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * Determine if a user has been set.
     *
     * @return bool
     */
    public function hasUser()
    {
        return !is_null($this->user);
    }

    /**
     * Set the request instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Get the token from the request.
     *
     * @return string|null
     */
    protected function getTokenFromRequest()
    {
        // Try Authorization header
        $authHeader = $this->request->header('Authorization');
        if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }

        // Try query parameter
        return $this->request->query('token');
    }

    /**
     * Validate and decode the JWT token.
     *
     * @param string $token
     * @return array|null
     */
    protected function validateToken($token)
    {
        try {
            $parts = explode('.', $token);
            
            if (count($parts) !== 3) {
                return null;
            }

            [$header, $payload, $signature] = $parts;

            // Decode payload (handle URL-safe base64)
            $decodedPayload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);

            if (!$decodedPayload) {
                return null;
            }

            // Check expiration
            if (isset($decodedPayload['exp']) && $decodedPayload['exp'] < time()) {
                return null;
            }

            // Verify signature (basic check) - convert to URL-safe base64 for comparison
            $expectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(
                hash_hmac('sha256', "$header.$payload", config('app.key', 'fallback-secret-key'), true)
            ));

            // Use hash_equals for timing-safe comparison
            if (!hash_equals($expectedSignature, $signature)) {
                return null;
            }

            return $decodedPayload;
        } catch (\Exception $e) {
            return null;
        }
    }
}

