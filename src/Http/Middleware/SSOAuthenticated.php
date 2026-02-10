<?php

namespace Baliprov\SSOBroker\Http\Middleware;

use Baliprov\SSOBroker\SSOBrokerManager;
use Closure;
use Illuminate\Http\Request;

class SSOAuthenticated
{
    /** @var SSOBrokerManager */
    protected $ssoManager;

    /**
     * @param SSOBrokerManager $ssoManager
     */
    public function __construct(SSOBrokerManager $ssoManager)
    {
        $this->ssoManager = $ssoManager;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!$this->ssoManager->isAuthenticated()) {
            return $this->handleUnauthenticated($request);
        }

        return $next($request);
    }

    /**
     * Handle unauthenticated request
     *
     * @param Request $request
     * @return mixed
     */
    protected function handleUnauthenticated(Request $request)
    {
        // Store intended URL
        $this->ssoManager->setIntendedUrl($request->fullUrl());

        // For API requests, return JSON response
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // For web requests, redirect to SSO
        return $this->ssoManager->redirectToSSO();
    }
}
