<?php

namespace Tymon\JWTAuth\Exceptions;

use Exception;

/**
 * Stub exception classes for JWT when tymon/jwt-auth package is not installed.
 * These files are only used as a fallback when the package is missing.
 */

class JWTException extends Exception
{
    //
}

class TokenExpiredException extends JWTException
{
    //
}

class TokenInvalidException extends JWTException
{
    //
}
