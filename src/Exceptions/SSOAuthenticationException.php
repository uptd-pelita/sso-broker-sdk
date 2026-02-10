<?php

namespace Baliprov\SSOBroker\Exceptions;

use Exception;

class SSOAuthenticationException extends Exception
{
    /** @var string */
    protected $errorType;

    /** @var object|null */
    protected $payload;

    /**
     * @param string $message
     * @param string $errorType
     * @param object|null $payload
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        $message = "SSO Authentication Failed",
        $errorType = 'authentication_failed',
        $payload = null,
        $code = 403,
        $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorType = $errorType;
        $this->payload = $payload;
    }

    /**
     * Get error type
     *
     * @return string
     */
    public function getErrorType()
    {
        return $this->errorType;
    }

    /**
     * Get payload data
     *
     * @return object|null
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * Create exception for invalid token
     *
     * @param string $message
     * @return self
     */
    public static function invalidToken($message = 'Invalid JWT token')
    {
        return new self($message, 'invalid_token');
    }

    /**
     * Create exception for session mismatch
     *
     * @return self
     */
    public static function sessionMismatch()
    {
        return new self('Session mismatch', 'session_mismatch');
    }

    /**
     * Create exception for not authorized
     *
     * @param object|null $payload
     * @return self
     */
    public static function notAuthorized($payload = null)
    {
        return new self(
            'User is not authorized to access this application',
            'not_authorized',
            $payload,
            403
        );
    }

    /**
     * Create exception for server error
     *
     * @param string $message
     * @return self
     */
    public static function serverError($message = 'SSO server error')
    {
        return new self($message, 'server_error', null, 500);
    }

    /**
     * Render the exception to HTTP response
     *
     * @param mixed $request
     * @return mixed
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => $this->errorType,
                'message' => $this->getMessage(),
            ], $this->getCode());
        }

        return redirect()
            ->route(config('sso-broker.not_authorized_route', 'not-authorized'))
            ->with('error', $this->getMessage());
    }
}
