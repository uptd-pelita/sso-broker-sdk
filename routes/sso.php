<?php

use Baliprov\SSOBroker\Http\Controllers\SSOBrokerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SSO Broker Routes
|--------------------------------------------------------------------------
|
| These routes handle SSO authentication. You can publish this file to
| your application's routes directory and customize as needed.
|
| To disable default routes, set 'load_default_routes' => false in config.
|
*/

$prefix = config('sso-broker.route_prefix', '');
$middleware = config('sso-broker.route_middleware', ['web']);

Route::middleware($middleware)->prefix($prefix)->group(function () {

    // Main authentication route - handles redirect to SSO and callback
    Route::match(['get', 'post'], '/sso/authenticate', [SSOBrokerController::class, 'authenticate'])
        ->name('sso.authenticate');

    // SSO callback route - receives token from SSO server
    Route::get('/auth-data', [SSOBrokerController::class, 'callback'])
        ->name('sso.callback');

    // Logout callback from SSO server (for centralized logout)
    Route::post('/sso/logout', [SSOBrokerController::class, 'logout'])
        ->name('sso.logout');

    // User-initiated logout
    Route::get('/logout', [SSOBrokerController::class, 'userLogout'])
        ->name('sso.user-logout');

    // Check authentication status (API)
    Route::get('/sso/check', [SSOBrokerController::class, 'checkAuth'])
        ->name('sso.check');

    // Get current user (API)
    Route::get('/sso/user', [SSOBrokerController::class, 'getUser'])
        ->name('sso.user');

});
