<?php

namespace Baliprov\SSOBroker;

use Baliprov\SSOBroker\Contracts\SSOAuthenticatorInterface;
use Baliprov\SSOBroker\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SSOBrokerManager implements SSOAuthenticatorInterface
{
    /** @var string */
    protected $ssoDomain;

    /** @var string */
    protected $ssoServerLink;

    /** @var string */
    protected $brokerCode;

    /** @var string */
    protected $jwtSecret;

    /** @var string */
    protected $protocol;

    /** @var string */
    protected $logoutLink;

    /** @var string */
    protected $callbackRoute;

    /** @var string */
    protected $redirectAfterLogin;

    /** @var string */
    protected $redirectAfterLogout;

    /** @var string */
    protected $notAuthorizedRoute;

    /** @var Client|null */
    protected $httpClient = null;

    /** @var array */
    protected $sessionKeys = [
        'authenticated' => 'UserIsAuthenticated',
        'user_data' => 'authUserData',
        'default_role' => 'defaultRole',
        'sso_user_id' => 'sso_user_id',
        'url_to_redirect' => 'urlToRedirect',
    ];

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->loadConfig($config);
    }

    /**
     * Load configuration from array or config file
     *
     * @param array $config
     * @return void
     */
    protected function loadConfig(array $config = [])
    {
        $this->ssoDomain = $config['sso_domain'] ?? config('sso-broker.sso_domain', '');
        $this->ssoServerLink = rtrim($this->ssoDomain, '/') . '/authBroker/';
        $this->brokerCode = $config['broker_code'] ?? config('sso-broker.broker_code', '');
        $this->jwtSecret = $config['jwt_secret'] ?? config('sso-broker.jwt_secret', 'SSO-JWT-SECRET-KEY');
        $this->protocol = $config['protocol'] ?? (request()->secure() ? 'https' : 'http');
        $this->callbackRoute = $config['callback_route'] ?? config('sso-broker.callback_route', '/authData');
        $this->redirectAfterLogin = $config['redirect_after_login'] ?? config('sso-broker.redirect_after_login', '/');
        $this->redirectAfterLogout = $config['redirect_after_logout'] ?? config('sso-broker.redirect_after_logout', '/');
        $this->notAuthorizedRoute = $config['not_authorized_route'] ?? config('sso-broker.not_authorized_route', 'not-authorized');

        $host = $_SERVER['HTTP_HOST'] ?? request()->getHost();
        $this->logoutLink = $config['logout_link'] ?? "{$this->protocol}://{$host}/keluar";
    }

    /**
     * Set custom HTTP client
     *
     * @param Client $client
     * @return self
     */
    public function setHttpClient(Client $client)
    {
        $this->httpClient = $client;
        return $this;
    }

    /**
     * Get HTTP client
     *
     * @return Client
     */
    protected function getHttpClient()
    {
        if (!$this->httpClient) {
            $this->httpClient = new Client([
                'timeout' => config('sso-broker.http_timeout', 30),
                'verify' => config('sso-broker.verify_ssl', true),
            ]);
        }
        return $this->httpClient;
    }

    /**
     * Set custom session keys
     *
     * @param array $keys
     * @return self
     */
    public function setSessionKeys(array $keys)
    {
        $this->sessionKeys = array_merge($this->sessionKeys, $keys);
        return $this;
    }

    /**
     * Main authentication entry point
     */
    public function authenticate(Request $request)
    {
        // Check if this is a callback from SSO server
        if ($token = $request->get('authData') ?? $request->input('authData')) {
            return $this->handleCallback($token);
        }

        // Check if already authenticated
        if ($this->isAuthenticated()) {
            return response()->json(['authenticated' => true]);
        }

        // Redirect to SSO server
        return $this->redirectToSSO();
    }

    /**
     * Handle SSO callback with token
     *
     * @param string $token
     * @return mixed
     */
    public function handleCallback($token)
    {
        try {
            $verifyResponse = $this->verifyTokenWithServer($token);

            if (empty($verifyResponse->status) || empty($verifyResponse->data)) {
                return $this->handleAuthError('Invalid JWT String data!');
            }

            $jwt = new JWT($this->jwtSecret);
            $jwt->setJWTString($verifyResponse->data);

            if (!$jwt->decodeJWT()) {
                return $this->handleAuthError('Invalid JWT data!');
            }

            $payload = $jwt->getPayloadJWT();

            // Validate session
            if (!$this->validateSession($payload)) {
                return $this->handleAuthError('Invalid browser session!');
            }

            // Check authorization (can be overridden)
            if (!$this->checkAuthorization($payload)) {
                return $this->handleNotAuthorized($payload);
            }

            // Store session data
            $this->storeSessionData($payload);

            // Hook for additional processing
            $this->afterSuccessfulAuth($payload);

            return redirect($payload->urlToRedirect ?? $this->redirectAfterLogin);

        } catch (GuzzleException $e) {
            return $this->handleGuzzleException($e);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Verify token with SSO server
     *
     * @param string $token
     * @return object
     */
    protected function verifyTokenWithServer($token)
    {
        $response = $this->getHttpClient()->post($this->ssoDomain . '/api/v1/auth/jwt/verify', [
            'form_params' => ['token' => $token]
        ]);

        return json_decode($response->getBody()->getContents());
    }

    /**
     * Validate session matches the request
     *
     * @param object $payload
     * @return bool
     */
    protected function validateSession($payload)
    {
        return session()->getId() === ($payload->sessionRequest ?? null);
    }

    /**
     * Check if user is authorized (can be overridden)
     *
     * @param object $payload
     * @return bool
     */
    protected function checkAuthorization($payload)
    {
        return !empty($payload->roles);
    }

    /**
     * Store authentication data in session
     *
     * @param object $payload
     * @return void
     */
    protected function storeSessionData($payload)
    {
        session([
            $this->sessionKeys['authenticated'] => 1,
            $this->sessionKeys['user_data'] => $payload,
            $this->sessionKeys['default_role'] => $payload->roles[0] ?? null,
            $this->sessionKeys['sso_user_id'] => $payload->user->id ?? null,
        ]);
    }

    /**
     * Hook for additional processing after successful authentication
     *
     * @param object $payload
     * @return void
     */
    protected function afterSuccessfulAuth($payload)
    {
        // Override in child class to add custom logic
    }

    /**
     * Redirect to SSO server for authentication
     */
    public function redirectToSSO()
    {
        $host = $_SERVER['HTTP_HOST'] ?? request()->getHost();

        $jwt = new JWT($this->jwtSecret);
        $jwt->setPayloadJWT([
            'redirect' => "{$this->protocol}://{$host}{$this->callbackRoute}",
            'urlToRedirect' => session($this->sessionKeys['url_to_redirect']),
            'logoutLink' => $this->logoutLink,
            'kode_broker' => $this->brokerCode,
            'sessionRequest' => session()->getId(),
        ]);
        $jwt->encodeJWT();

        return redirect($this->ssoServerLink . $jwt->getJWTString());
    }

    /**
     * Build JWT payload for redirect (can be extended)
     *
     * @return array
     */
    protected function buildRedirectPayload()
    {
        $host = $_SERVER['HTTP_HOST'] ?? request()->getHost();

        return [
            'redirect' => "{$this->protocol}://{$host}{$this->callbackRoute}",
            'urlToRedirect' => session($this->sessionKeys['url_to_redirect']),
            'logoutLink' => $this->logoutLink,
            'kode_broker' => $this->brokerCode,
            'sessionRequest' => session()->getId(),
        ];
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        // Handle remote logout callback
        if ($sessionId = $request->get('sessionId')) {
            Session::getHandler()->destroy($sessionId);
        }

        session()->flush();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Full logout with redirect
     */
    public function logoutAndRedirect(Request $request)
    {
        session()->flush();

        // Redirect to SSO server logout if configured
        $ssoLogoutUrl = config('sso-broker.sso_logout_url');
        if ($ssoLogoutUrl) {
            return redirect($ssoLogoutUrl);
        }

        return redirect($this->redirectAfterLogout);
    }

    /**
     * Check if user is authenticated
     *
     * @return bool
     */
    public function isAuthenticated()
    {
        return session()->has($this->sessionKeys['user_data']);
    }

    /**
     * Get authenticated user data
     *
     * @return object|null
     */
    public function getUser()
    {
        return session($this->sessionKeys['user_data']);
    }

    /**
     * Get user roles
     *
     * @return array
     */
    public function getRoles()
    {
        $user = $this->getUser();
        return $user->roles ?? [];
    }

    /**
     * Get default role
     */
    public function getDefaultRole()
    {
        return session($this->sessionKeys['default_role']);
    }

    /**
     * Get SSO user ID
     */
    public function getSSOUserId()
    {
        return session($this->sessionKeys['sso_user_id']);
    }

    /**
     * Check if user has specific role
     *
     * @param string $role
     * @return bool
     */
    public function hasRole($role)
    {
        return in_array($role, $this->getRoles());
    }

    /**
     * Check if user has any of the specified roles
     *
     * @param array $roles
     * @return bool
     */
    public function hasAnyRole(array $roles)
    {
        return !empty(array_intersect($roles, $this->getRoles()));
    }

    /**
     * Handle authentication error
     */
    protected function handleAuthError(string $message)
    {
        abort(403, $message);
    }

    /**
     * Handle not authorized
     *
     * @param object $payload
     * @return mixed
     */
    protected function handleNotAuthorized($payload)
    {
        return redirect()
            ->route($this->notAuthorizedRoute)
            ->with('error', 'Anda tidak memiliki akses ke aplikasi ini');
    }

    /**
     * Handle Guzzle exception
     */
    protected function handleGuzzleException(GuzzleException $e)
    {
        return redirect()->to($this->ssoDomain);
    }

    /**
     * Handle generic exception
     */
    protected function handleException(\Exception $e)
    {
        throw $e;
    }

    /**
     * Set the URL to redirect after login
     *
     * @param string $url
     * @return self
     */
    public function setIntendedUrl($url)
    {
        session([$this->sessionKeys['url_to_redirect'] => $url]);
        return $this;
    }

    /**
     * Get SSO domain
     *
     * @return string
     */
    public function getSSODomain()
    {
        return $this->ssoDomain;
    }

    /**
     * Get broker code
     *
     * @return string
     */
    public function getBrokerCode()
    {
        return $this->brokerCode;
    }
}
