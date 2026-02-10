<?php

namespace Baliprov\SSOBroker\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed authenticate(\Illuminate\Http\Request $request)
 * @method static mixed handleCallback(string $token)
 * @method static mixed redirectToSSO()
 * @method static mixed logout(\Illuminate\Http\Request $request)
 * @method static mixed logoutAndRedirect(\Illuminate\Http\Request $request)
 * @method static bool isAuthenticated()
 * @method static object|null getUser()
 * @method static array getRoles()
 * @method static mixed getDefaultRole()
 * @method static mixed getSSOUserId()
 * @method static bool hasRole(string $role)
 * @method static bool hasAnyRole(array $roles)
 * @method static self setIntendedUrl(string $url)
 * @method static string getSSODomain()
 * @method static string getBrokerCode()
 * @method static self setHttpClient(\GuzzleHttp\Client $client)
 * @method static self setSessionKeys(array $keys)
 *
 * @see \Baliprov\SSOBroker\SSOBrokerManager
 */
class SSOBroker extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'sso-broker';
    }
}
