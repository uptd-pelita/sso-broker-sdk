<?php

namespace Baliprov\SSOBroker\Http\Middleware;

use Baliprov\SSOBroker\SSOBrokerManager;
use Closure;
use Illuminate\Http\Request;

class SSOHasRole
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
     * @param string ...$roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!$this->ssoManager->isAuthenticated()) {
            return $this->handleUnauthenticated($request);
        }

        if (!$this->ssoManager->hasAnyRole($roles)) {
            return $this->handleUnauthorized($request);
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
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        return $this->ssoManager->redirectToSSO();
    }

    /**
     * Handle unauthorized request
     *
     * @param Request $request
     * @return mixed
     */
    protected function handleUnauthorized(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return redirect()
            ->route(config('sso-broker.not_authorized_route', 'not-authorized'))
            ->with('error', 'Anda tidak memiliki akses ke halaman ini');
    }
}
