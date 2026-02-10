# SSO Broker SDK for Bali Province Applications

A Laravel package for integrating Single Sign-On (SSO) authentication with Bali Province SSO Server.

## Requirements

- PHP 7.4+ or PHP 8.0+
- Laravel 6.0, 7.0, 8.0, 9.0, 10.0, or 11.0
- GuzzleHTTP 6.0+ or 7.0+

## Installation

### Via Composer (Private GitLab Repository)

Since this is a private repository, you need to configure Composer to access it.

**Step 1:** Add the repository and package to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "git@gitlab.baliprov.dev:spbe-bali/sso-broker-sdk.git"
        }
    ],
    "require": {
        "spbe-bali/sso-broker-sdk": "dev-master"
    }
}
```

**Step 2:** Run composer update:

```bash
composer update spbe-bali/sso-broker-sdk
```

> **Note:** Ensure your SSH key is added to GitLab and has access to the repository. For CI/CD pipelines, use deploy tokens or deploy keys.

### Using Tagged Versions (Recommended for Production)

Once tags are created in the repository, use specific versions:

```json
{
    "require": {
        "spbe-bali/sso-broker-sdk": "^1.0"
    }
}
```

To create a version tag:

```bash
cd sso-baliprov-sdk
git tag -a v1.0.0 -m "Initial stable release"
git push origin v1.0.0
```

## Configuration

### 1. Publish Configuration

```bash
php artisan vendor:publish --tag=sso-broker-config
```

### 2. Environment Variables

Add the following to your `.env` file:

```env
SSO_DOMAIN=https://sso.baliprov.go.id
SSO_BROKER_CODE=your-broker-code
SSO_JWT_SECRET=your-jwt-secret
SSO_CALLBACK_ROUTE=/authData
SSO_REDIRECT_AFTER_LOGIN=/dashboard
SSO_REDIRECT_AFTER_LOGOUT=/
SSO_NOT_AUTHORIZED_ROUTE=not-authorized
```

### 3. Optional: Publish Routes

If you want to customize routes:

```bash
php artisan vendor:publish --tag=sso-broker-routes
```

Then set `SSO_LOAD_DEFAULT_ROUTES=false` in your `.env` file.

## Basic Usage

### Protecting Routes with Middleware

```php
// In routes/web.php

// Require SSO authentication
Route::middleware('sso.auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});

// Require specific role
Route::middleware('sso.role:admin')->group(function () {
    Route::get('/admin', [AdminController::class, 'index']);
});

// Require any of multiple roles
Route::middleware('sso.role:admin,editor,manager')->group(function () {
    Route::get('/manage', [ManageController::class, 'index']);
});
```

### Using the Facade

```php
use Baliprov\SSOBroker\Facades\SSOBroker;

// Check if authenticated
if (SSOBroker::isAuthenticated()) {
    // User is logged in
}

// Get user data
$user = SSOBroker::getUser();

// Get user roles
$roles = SSOBroker::getRoles();

// Check specific role
if (SSOBroker::hasRole('admin')) {
    // User has admin role
}

// Check any of multiple roles
if (SSOBroker::hasAnyRole(['admin', 'editor'])) {
    // User has at least one of these roles
}

// Get SSO user ID
$ssoUserId = SSOBroker::getSSOUserId();

// Set intended URL before redirecting to SSO
SSOBroker::setIntendedUrl('/dashboard');
```

### Manual Authentication in Controller

```php
use Baliprov\SSOBroker\SSOBrokerManager;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected SSOBrokerManager $sso;

    public function __construct(SSOBrokerManager $sso)
    {
        $this->sso = $sso;
    }

    public function login(Request $request)
    {
        return $this->sso->authenticate($request);
    }

    public function logout(Request $request)
    {
        return $this->sso->logoutAndRedirect($request);
    }
}
```

## Extending the SDK

### Custom Controller

Create a custom controller that extends the base:

```php
<?php

namespace App\Http\Controllers;

use Baliprov\SSOBroker\Http\Controllers\SSOBrokerController as BaseSSOController;
use Illuminate\Http\Request;

class SSOController extends BaseSSOController
{
    /**
     * Override callback handling
     */
    public function callback(Request $request)
    {
        // Custom logic before callback
        $this->beforeCallback($request);

        $response = parent::callback($request);

        // Custom logic after callback
        $this->afterCallback($request);

        return $response;
    }

    protected function beforeCallback(Request $request)
    {
        // Add custom logging, etc.
    }

    protected function afterCallback(Request $request)
    {
        // Sync user to local database, etc.
    }
}
```

### Custom SSO Manager

Create a custom manager with additional functionality:

```php
<?php

namespace App\Services;

use App\Models\User;
use Baliprov\SSOBroker\SSOBrokerManager;

class CustomSSOManager extends SSOBrokerManager
{
    /**
     * Custom authorization check
     */
    protected function checkAuthorization(object $payload): bool
    {
        // Only allow specific roles
        $allowedRoles = ['admin', 'staff', 'operator'];
        $userRoles = $payload->roles ?? [];

        return !empty(array_intersect($allowedRoles, $userRoles));
    }

    /**
     * Hook after successful authentication
     */
    protected function afterSuccessfulAuth(object $payload): void
    {
        // Sync user to local database
        User::findOrCreateFromSSO($payload);

        // Log authentication
        activity()
            ->causedBy(User::bySSOUserId($payload->user->id)->first())
            ->log('User logged in via SSO');
    }

    /**
     * Custom error handling
     */
    protected function handleAuthError(string $message)
    {
        // Log error
        \Log::error('SSO Auth Error: ' . $message);

        // Custom redirect
        return redirect()->route('login.error')->with('error', $message);
    }
}
```

Register your custom manager in a service provider:

```php
// In AppServiceProvider.php

public function register()
{
    $this->app->singleton('sso-broker', function ($app) {
        return new \App\Services\CustomSSOManager();
    });
}
```

### Using with Local User Model

Add the `HasSSOAuthentication` trait to your User model:

```php
<?php

namespace App\Models;

use Baliprov\SSOBroker\Contracts\SSOUserInterface;
use Baliprov\SSOBroker\Traits\HasSSOAuthentication;
use Illuminate\Database\Eloquent\Model;

class User extends Model implements SSOUserInterface
{
    use HasSSOAuthentication;

    protected $fillable = [
        'sso_user_id',
        'name',
        'email',
        'nip',
        'unit_kerja',
    ];

    /**
     * Customize SSO ID column name
     */
    protected static function getSSOIdColumn(): string
    {
        return 'sso_user_id';
    }

    /**
     * Customize attribute mapping for new users
     */
    protected static function getSSOAttributeMapping(): array
    {
        return [
            'sso_user_id' => 'id',
            'name' => 'name',
            'email' => 'email',
            'nip' => 'nip',
            'unit_kerja' => fn($payload) => $payload->user->unit_kerja->nama ?? null,
        ];
    }

    /**
     * Customize attribute mapping for updates
     */
    protected static function getSSOUpdateMapping(): array
    {
        return [
            'name' => 'name',
            'email' => 'email',
        ];
    }
}
```

Usage:

```php
use App\Models\User;
use Baliprov\SSOBroker\Facades\SSOBroker;

// Get current authenticated user as model
$user = User::currentSSOUser();

// Or manually find/create from payload
$payload = SSOBroker::getUser();
$user = User::findOrCreateFromSSO($payload);

// Check SSO roles on model
if ($user->hasSSORole('admin')) {
    // ...
}
```

### Custom Routes

Disable default routes and define your own:

```php
// In .env
SSO_LOAD_DEFAULT_ROUTES=false
```

```php
// In routes/web.php

use App\Http\Controllers\SSOController;

Route::prefix('auth')->group(function () {
    Route::get('/login', [SSOController::class, 'authenticate'])->name('login');
    Route::get('/callback', [SSOController::class, 'callback'])->name('sso.callback');
    Route::post('/logout-callback', [SSOController::class, 'logout'])->name('sso.logout');
    Route::get('/logout', [SSOController::class, 'userLogout'])->name('logout');
});
```

### Custom Middleware

Extend the middleware for custom behavior:

```php
<?php

namespace App\Http\Middleware;

use Baliprov\SSOBroker\Http\Middleware\SSOAuthenticated as BaseMiddleware;
use Illuminate\Http\Request;

class SSOAuth extends BaseMiddleware
{
    protected function handleUnauthenticated(Request $request)
    {
        // Store additional data before redirect
        session(['login_attempt_url' => $request->fullUrl()]);

        // Custom redirect logic
        if ($request->is('api/*')) {
            return response()->json([
                'error' => 'Unauthorized',
                'login_url' => route('sso.authenticate'),
            ], 401);
        }

        return parent::handleUnauthenticated($request);
    }
}
```

Register in `app/Http/Kernel.php`:

```php
protected $middlewareAliases = [
    // ...
    'sso.auth' => \App\Http\Middleware\SSOAuth::class,
];
```

## Default Routes

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET/POST | /sso/authenticate | sso.authenticate | Initiate SSO login |
| GET | /authData | sso.callback | SSO callback |
| POST | /sso/logout | sso.logout | Logout callback from SSO |
| GET | /keluar | sso.user-logout | User-initiated logout |
| GET | /sso/check | sso.check | Check auth status (API) |
| GET | /sso/user | sso.user | Get user data (API) |

## Configuration Options

| Key | Description | Default |
|-----|-------------|---------|
| `sso_domain` | SSO server URL | `https://sso.baliprov.go.id` |
| `broker_code` | Broker identifier | `''` |
| `jwt_secret` | JWT secret key | `SSO-JWT-SECRET-KEY` |
| `callback_route` | Callback URL path | `/authData` |
| `redirect_after_login` | Post-login redirect | `/` |
| `redirect_after_logout` | Post-logout redirect | `/` |
| `not_authorized_route` | Unauthorized route name | `not-authorized` |
| `load_default_routes` | Load default routes | `true` |
| `route_prefix` | Route prefix | `''` |
| `route_middleware` | Route middleware | `['web']` |
| `http_timeout` | HTTP request timeout | `30` |
| `verify_ssl` | Verify SSL certificates | `true` |

## Helper Methods

### JWT Class

```php
use Baliprov\SSOBroker\JWT\JWT;

// Encode data to JWT
$token = JWT::encode(['user_id' => 1, 'name' => 'John']);

// Decode JWT
$payload = JWT::decode($token);

// Or use instance methods
$jwt = new JWT();
$jwt->setPayloadJWT(['user_id' => 1]);
$token = $jwt->encodeJWT();

$jwt->setJWTString($token);
$payload = $jwt->decodeJWT();
```

## Migration Example

Create a migration for the `sso_user_id` column:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('sso_user_id')->nullable()->unique()->after('id');
            $table->index('sso_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('sso_user_id');
        });
    }
};
```

## Troubleshooting

### "Invalid browser session" Error

This occurs when the session ID doesn't match. Ensure:
1. Sessions are properly configured
2. The same domain is used for login and callback
3. Session middleware is applied to SSO routes

### SSL Certificate Issues

In development, you can disable SSL verification:

```env
SSO_VERIFY_SSL=false
```

**Never disable in production!**

### Custom Session Driver

If using a custom session driver, ensure it's properly configured before SSO routes are accessed.

## License

MIT License. See [LICENSE](LICENSE) for details.

## Contributing

Contributions are welcome! Please submit pull requests with tests.

## Support

For issues and questions, please open an issue on the repository.
