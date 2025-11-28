<?php

namespace Tymon\JWTAuth\Contracts;

/**
 * Stub interface for JWTSubject when tymon/jwt-auth package is not installed.
 * This file is only used as a fallback when the package is missing.
 */
interface JWTSubject
{
    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier();

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims();
}

