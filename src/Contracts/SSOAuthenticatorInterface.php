<?php

namespace Baliprov\SSOBroker\Contracts;

use Illuminate\Http\Request;

interface SSOAuthenticatorInterface
{
    /**
     * Handle authentication request
     *
     * @param Request $request
     * @return mixed
     */
    public function authenticate(Request $request);

    /**
     * Handle SSO response/callback
     *
     * @param string $token
     * @return mixed
     */
    public function handleCallback($token);

    /**
     * Redirect to SSO server
     *
     * @return mixed
     */
    public function redirectToSSO();

    /**
     * Handle logout
     *
     * @param Request $request
     * @return mixed
     */
    public function logout(Request $request);

    /**
     * Check if user is authenticated
     *
     * @return bool
     */
    public function isAuthenticated();

    /**
     * Get authenticated user data
     *
     * @return object|null
     */
    public function getUser();
}
