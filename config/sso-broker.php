<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SSO Server Domain
    |--------------------------------------------------------------------------
    |
    | The base URL of the SSO server. This should include the protocol
    | (https://) but not a trailing slash.
    |
    */
    'sso_domain' => env('SSO_DOMAIN', 'https://sso.baliprov.go.id'),

    /*
    |--------------------------------------------------------------------------
    | Broker Code
    |--------------------------------------------------------------------------
    |
    | The unique broker code assigned to this application by the SSO server.
    | This is used to identify the application during authentication.
    |
    */
    'broker_code' => env('SSO_BROKER_CODE', ''),

    /*
    |--------------------------------------------------------------------------
    | JWT Secret
    |--------------------------------------------------------------------------
    |
    | The secret key used for JWT encoding/decoding. This should match
    | the secret configured on the SSO server.
    |
    */
    'jwt_secret' => env('SSO_JWT_SECRET', 'SSO-JWT-SECRET-KEY'),

    /*
    |--------------------------------------------------------------------------
    | Callback Route
    |--------------------------------------------------------------------------
    |
    | The route path that the SSO server will redirect to after authentication.
    | This should match the route defined in your application.
    |
    */
    'callback_route' => env('SSO_CALLBACK_ROUTE', '/auth-data'),

    /*
    |--------------------------------------------------------------------------
    | Redirect After Login
    |--------------------------------------------------------------------------
    |
    | The default URL to redirect to after successful authentication.
    | This can be overridden by setting the intended URL before login.
    |
    */
    'redirect_after_login' => env('SSO_REDIRECT_AFTER_LOGIN', '/'),

    /*
    |--------------------------------------------------------------------------
    | Redirect After Logout
    |--------------------------------------------------------------------------
    |
    | The URL to redirect to after logging out.
    |
    */
    'redirect_after_logout' => env('SSO_REDIRECT_AFTER_LOGOUT', '/'),

    /*
    |--------------------------------------------------------------------------
    | Not Authorized Route
    |--------------------------------------------------------------------------
    |
    | The route name to redirect to when user is not authorized.
    |
    */
    'not_authorized_route' => env('SSO_NOT_AUTHORIZED_ROUTE', 'not-authorized'),

    /*
    |--------------------------------------------------------------------------
    | SSO Logout URL
    |--------------------------------------------------------------------------
    |
    | The URL on the SSO server to redirect for centralized logout.
    | Leave null to just clear local session without SSO server logout.
    |
    */
    'sso_logout_url' => env('SSO_LOGOUT_URL', null),

    /*
    |--------------------------------------------------------------------------
    | Load Default Routes
    |--------------------------------------------------------------------------
    |
    | Whether to automatically load the default SSO routes. Set to false
    | if you want to define custom routes in your application.
    |
    */
    'load_default_routes' => env('SSO_LOAD_DEFAULT_ROUTES', true),

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | The prefix for all SSO routes. Default routes will be prefixed with
    | this value if load_default_routes is true.
    |
    */
    'route_prefix' => env('SSO_ROUTE_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware to apply to SSO routes.
    |
    */
    'route_middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeout
    |--------------------------------------------------------------------------
    |
    | Timeout in seconds for HTTP requests to the SSO server.
    |
    */
    'http_timeout' => env('SSO_HTTP_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Verify SSL
    |--------------------------------------------------------------------------
    |
    | Whether to verify SSL certificates when making requests to SSO server.
    | Should be true in production.
    |
    */
    'verify_ssl' => env('SSO_VERIFY_SSL', true),

    /*
    |--------------------------------------------------------------------------
    | Session Keys
    |--------------------------------------------------------------------------
    |
    | Custom session key names for storing authentication data.
    |
    */
    'session_keys' => [
        'authenticated' => 'UserIsAuthenticated',
        'user_data' => 'authUserData',
        'default_role' => 'defaultRole',
        'sso_user_id' => 'sso_user_id',
        'url_to_redirect' => 'urlToRedirect',
    ],

];
