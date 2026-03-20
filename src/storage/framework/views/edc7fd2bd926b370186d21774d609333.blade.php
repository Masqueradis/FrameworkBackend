---
name: passport-development
description: "Develops OAuth2 API authentication with Laravel Passport. Activates when installing or configuring Passport; setting up OAuth2 grants (authorization code, client credentials, personal access tokens, device authorization); managing OAuth clients; protecting API routes with token authentication; defining or checking token scopes; configuring SPA cookie authentication; handling token lifetimes and refresh tokens; or when the user mentions Passport, OAuth2, API tokens, bearer tokens, or API authentication. Make sure to use this skill whenever the user works with OAuth2, API tokens, or third-party API access, even if they don't explicitly mention Passport."
license: MIT
metadata:
  author: laravel
---
@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Passport OAuth2 Authentication

## Documentation First

**Always use ___SINGLE_BACKTICK___search-docs___SINGLE_BACKTICK___ before writing Passport code.** The documentation covers every grant type, configuration option, and edge case in detail. This skill teaches you how to navigate Passport — the docs have the implementation specifics.

___SINGLE_BACKTICK______SINGLE_BACKTICK______SINGLE_BACKTICK___
search-docs(queries: ["Passport installation"], packages: ["laravel/framework@12.x"])
___SINGLE_BACKTICK______SINGLE_BACKTICK______SINGLE_BACKTICK___

The Passport docs live under the ___SINGLE_BACKTICK___laravel/framework___SINGLE_BACKTICK___ package — not ___SINGLE_BACKTICK___laravel/passport___SINGLE_BACKTICK___.

## When to Apply

Activate this skill when:

- Installing or configuring Passport
- Setting up OAuth2 authorization grants
- Creating or managing OAuth clients
- Protecting API routes with token authentication
- Defining or checking token scopes
- Configuring SPA cookie-based authentication
- Choosing between Passport and Sanctum

## Passport vs. Sanctum

**Passport** is a full OAuth2 server — use it when third-party applications need to consume your API and when you need OAuth2 authorization code grants, client credentials for machine-to-machine auth, or device authorization flow.

**Sanctum** is simpler — use it when first-party SPAs, third parties, or mobile apps consume the API but you don't need the full OAuth2 grant flows.

## Installation

Three steps are always required:

### 1. Install Passport

___SINGLE_BACKTICK______SINGLE_BACKTICK______SINGLE_BACKTICK___bash
{{ $assist->artisanCommand('install:api --passport') }}
___SINGLE_BACKTICK______SINGLE_BACKTICK______SINGLE_BACKTICK___

This publishes migrations, generates encryption keys, and registers routes.

### 2. Configure the User model

The User model needs both the ___SINGLE_BACKTICK___HasApiTokens___SINGLE_BACKTICK___ trait AND the ___SINGLE_BACKTICK___OAuthenticatable___SINGLE_BACKTICK___ interface. Missing the interface is the most common Passport setup mistake — it causes runtime errors that can be confusing to debug.

___SINGLE_BACKTICK______SINGLE_BACKTICK______SINGLE_BACKTICK___php
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable implements OAuthenticatable
{
    use HasApiTokens;
}
___SINGLE_BACKTICK______SINGLE_BACKTICK______SINGLE_BACKTICK___

### 3. Configure the auth guard

The ___SINGLE_BACKTICK___api___SINGLE_BACKTICK___ guard must use the ___SINGLE_BACKTICK___passport___SINGLE_BACKTICK___ driver in ___SINGLE_BACKTICK___config/auth.php___SINGLE_BACKTICK___. Using ___SINGLE_BACKTICK___token___SINGLE_BACKTICK___ or ___SINGLE_BACKTICK___sanctum___SINGLE_BACKTICK___ here silently breaks Passport authentication.

___SINGLE_BACKTICK______SINGLE_BACKTICK______SINGLE_BACKTICK___php
'guards' => [
    'api' => [
        'driver' => 'passport',
        'provider' => 'users',
    ],
],
___SINGLE_BACKTICK______SINGLE_BACKTICK______SINGLE_BACKTICK___

## Choosing a Grant Type

Matching the right grant to the use case is the most important Passport decision. Use ___SINGLE_BACKTICK___search-docs___SINGLE_BACKTICK___ for implementation details of any grant.

| Use Case | Grant Type | Client Flag |
|----------|-----------|-------------|
| Third-party app accessing user data | Authorization Code | (default) |
| Mobile/SPA without client secret | Authorization Code + PKCE | ___SINGLE_BACKTICK___--public___SINGLE_BACKTICK___ |
| Machine-to-machine, no user context | Client Credentials | ___SINGLE_BACKTICK___--client___SINGLE_BACKTICK___ |
| User-generated API keys | Personal Access Tokens | ___SINGLE_BACKTICK___--personal___SINGLE_BACKTICK___ |
| Smart TV, CLI, IoT devices | Device Authorization | ___SINGLE_BACKTICK___--device___SINGLE_BACKTICK___ |

**Legacy grants** (Password, Implicit) are disabled by default and not recommended. They must be explicitly enabled with ___SINGLE_BACKTICK___Passport::enablePasswordGrant()___SINGLE_BACKTICK___ or ___SINGLE_BACKTICK___Passport::enableImplicitGrant()___SINGLE_BACKTICK___.

## Client Management

Create clients with the appropriate flag for the grant type:

___SINGLE_BACKTICK______SINGLE_BACKTICK______SINGLE_BACKTICK___bash
{{ $assist->artisanCommand('passport:client') }}              # Authorization code
{{ $assist->artisanCommand('passport:client --public') }}     # PKCE (no secret)
{{ $assist->artisanCommand('passport:client --client') }}     # Client credentials
{{ $assist->artisanCommand('passport:client --personal') }}   # Personal access tokens
{{ $assist->artisanCommand('passport:client --device') }}     # Device authorization
___SINGLE_BACKTICK______SINGLE_BACKTICK______SINGLE_BACKTICK___

Additional flags: ___SINGLE_BACKTICK___--name=___SINGLE_BACKTICK___, ___SINGLE_BACKTICK___--redirect_uri=___SINGLE_BACKTICK___, ___SINGLE_BACKTICK___--provider=___SINGLE_BACKTICK___.

Client secrets are hashed by default — the plain-text secret is only shown at creation time and cannot be retrieved later.

## Protecting Routes

Apply ___SINGLE_BACKTICK___auth:api___SINGLE_BACKTICK___ middleware. Clients send tokens via the ___SINGLE_BACKTICK___Authorization: Bearer <token>___SINGLE_BACKTICK___ header.

___SINGLE_BACKTICK______SINGLE_BACKTICK______SINGLE_BACKTICK___php
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');
___SINGLE_BACKTICK______SINGLE_BACKTICK______SINGLE_BACKTICK___

### Scope Enforcement

Scope middleware must come alongside ___SINGLE_BACKTICK___auth:api___SINGLE_BACKTICK___:

- ___SINGLE_BACKTICK___CheckToken::using('scope1', 'scope2')___SINGLE_BACKTICK___ — requires ALL listed scopes
- ___SINGLE_BACKTICK___CheckTokenForAnyScope::using('scope1', 'scope2')___SINGLE_BACKTICK___ — requires ANY listed scope
- ___SINGLE_BACKTICK___EnsureClientIsResourceOwner::using('scope1')___SINGLE_BACKTICK___ — restricts to client credential tokens

___SINGLE_BACKTICK______SINGLE_BACKTICK______SINGLE_BACKTICK___php
use Laravel\Passport\Http\Middleware\CheckToken;

Route::get('/orders', function () {
    // ...
})->middleware(['auth:api', CheckToken::using('orders:read')]);
___SINGLE_BACKTICK______SINGLE_BACKTICK______SINGLE_BACKTICK___

### Programmatic scope checking

___SINGLE_BACKTICK______SINGLE_BACKTICK______SINGLE_BACKTICK___php
if ($request->user()->tokenCan('place-orders')) {
    // ...
}
___SINGLE_BACKTICK______SINGLE_BACKTICK______SINGLE_BACKTICK___

Use ___SINGLE_BACKTICK___search-docs___SINGLE_BACKTICK___ for full scope middleware registration and usage patterns.

## Key Configuration

Configure in ___SINGLE_BACKTICK___AppServiceProvider::boot()___SINGLE_BACKTICK___. Use ___SINGLE_BACKTICK___search-docs___SINGLE_BACKTICK___ for the full list of options.

___SINGLE_BACKTICK______SINGLE_BACKTICK______SINGLE_BACKTICK___php
// Token lifetimes (each is independent)
Passport::tokensExpireIn(now()->addDays(15));
Passport::refreshTokensExpireIn(now()->addDays(30));
Passport::personalAccessTokensExpireIn(now()->addMonths(6));

// Define scopes
Passport::tokensCan([
    'place-orders' => 'Place orders',
    'check-status' => 'Check order status',
]);
___SINGLE_BACKTICK______SINGLE_BACKTICK______SINGLE_BACKTICK___

## SPA Cookie Authentication

For first-party SPAs, the ___SINGLE_BACKTICK___CreateFreshApiToken___SINGLE_BACKTICK___ middleware issues a ___SINGLE_BACKTICK___laravel_token___SINGLE_BACKTICK___ cookie containing an encrypted JWT. The SPA must include CSRF tokens — missing the ___SINGLE_BACKTICK___X-CSRF-TOKEN___SINGLE_BACKTICK___ or ___SINGLE_BACKTICK___X-XSRF-TOKEN___SINGLE_BACKTICK___ header causes 419 errors.

Use ___SINGLE_BACKTICK___search-docs___SINGLE_BACKTICK___ for setup details — this feature has specific CSRF and cookie configuration requirements.

## Testing

Passport provides helpers to bypass full OAuth flows in tests:

___SINGLE_BACKTICK______SINGLE_BACKTICK______SINGLE_BACKTICK___php
Passport::actingAs($user, ['scope1', 'scope2']);
Passport::actingAsClient($client, ['scope1']);
___SINGLE_BACKTICK______SINGLE_BACKTICK______SINGLE_BACKTICK___

## Token Maintenance

___SINGLE_BACKTICK______SINGLE_BACKTICK______SINGLE_BACKTICK___bash
{{ $assist->artisanCommand('passport:purge') }}              # Purge revoked & expired
{{ $assist->artisanCommand('passport:purge --revoked') }}    # Only revoked
{{ $assist->artisanCommand('passport:purge --expired') }}    # Only expired
___SINGLE_BACKTICK______SINGLE_BACKTICK______SINGLE_BACKTICK___

Schedule ___SINGLE_BACKTICK___passport:purge___SINGLE_BACKTICK___ for regular expired token clean-up.

## Events

All in ___SINGLE_BACKTICK___Laravel\Passport\Events___SINGLE_BACKTICK___: ___SINGLE_BACKTICK___AccessTokenCreated___SINGLE_BACKTICK___, ___SINGLE_BACKTICK___AccessTokenRevoked___SINGLE_BACKTICK___, ___SINGLE_BACKTICK___RefreshTokenCreated___SINGLE_BACKTICK___.

## Common Pitfalls

- **Missing ___SINGLE_BACKTICK___OAuthenticatable___SINGLE_BACKTICK___ interface** — both the ___SINGLE_BACKTICK___HasApiTokens___SINGLE_BACKTICK___ trait and the ___SINGLE_BACKTICK___OAuthenticatable___SINGLE_BACKTICK___ interface are required on the User model. Missing the interface causes runtime errors.
- **Wrong guard driver** — the ___SINGLE_BACKTICK___api___SINGLE_BACKTICK___ guard must use ___SINGLE_BACKTICK___passport___SINGLE_BACKTICK___, not ___SINGLE_BACKTICK___token___SINGLE_BACKTICK___ or ___SINGLE_BACKTICK___sanctum___SINGLE_BACKTICK___. This fails silently.
- **Token lifetime confusion** — access token, refresh token, and personal access token lifetimes are all independent settings.
- **Missing CSRF for SPA cookie auth** — ___SINGLE_BACKTICK___CreateFreshApiToken___SINGLE_BACKTICK___ requires CSRF tokens. Use ___SINGLE_BACKTICK___Passport::ignoreCsrfToken()___SINGLE_BACKTICK___ only if you understand the security implications.
- **Client secrets are hashed** — the plain-text secret is only available at creation time.
- **Legacy grants are disabled** — Password and Implicit grants must be explicitly enabled and are not recommended.
