<?php

namespace Baliprov\SSOBroker\Http\Controllers;

use Baliprov\SSOBroker\SSOBrokerManager;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class SSOBrokerController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /** @var SSOBrokerManager */
    protected $ssoManager;

    /**
     * @param SSOBrokerManager|null $ssoManager
     */
    public function __construct($ssoManager = null)
    {
        $this->ssoManager = $ssoManager ?? app(SSOBrokerManager::class);
    }

    /**
     * Handle SSO authentication
     * Route: GET/POST /sso/authenticate or custom
     */
    public function authenticate(Request $request)
    {
        return $this->ssoManager->authenticate($request);
    }

    /**
     * Handle SSO callback
     * Route: GET /authData or custom callback route
     */
    public function callback(Request $request)
    {
        $token = $request->get('authData') ?? $request->input('authData');

        if (!$token) {
            return $this->handleMissingToken();
        }

        return $this->ssoManager->handleCallback($token);
    }

    /**
     * Handle logout from SSO server (callback)
     * Route: POST /sso/logout or custom
     */
    public function logout(Request $request)
    {
        return $this->ssoManager->logout($request);
    }

    /**
     * User initiated logout with redirect
     * Route: GET /keluar or custom
     */
    public function userLogout(Request $request)
    {
        return $this->ssoManager->logoutAndRedirect($request);
    }

    /**
     * Check authentication status (API)
     * Route: GET /sso/check
     */
    public function checkAuth()
    {
        return response()->json([
            'authenticated' => $this->ssoManager->isAuthenticated(),
            'user' => $this->ssoManager->getUser(),
            'roles' => $this->ssoManager->getRoles(),
        ]);
    }

    /**
     * Get authenticated user (API)
     * Route: GET /sso/user
     */
    public function getUser()
    {
        if (!$this->ssoManager->isAuthenticated()) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        return response()->json([
            'user' => $this->ssoManager->getUser(),
            'roles' => $this->ssoManager->getRoles(),
            'default_role' => $this->ssoManager->getDefaultRole(),
        ]);
    }

    /**
     * Handle missing token in callback
     */
    protected function handleMissingToken()
    {
        return redirect('/')->with('error', 'Missing authentication token');
    }

    /**
     * Override to customize authentication response
     */
    protected function authenticatedResponse($user)
    {
        return response()->json(['authenticated' => true, 'user' => $user]);
    }

    /**
     * Override to customize not authorized response
     */
    protected function notAuthorizedResponse()
    {
        return response()->json(['error' => 'Not authorized'], 403);
    }
}
