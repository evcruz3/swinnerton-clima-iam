# Developer Guide: Integrating Keycloak with PHP Applications

This guide explains how to integrate Keycloak authentication into your PHP application using OpenID Connect (OIDC). We'll use the `jumbojett/openid-connect-php` library, which simplifies the OAuth 2.0 / OIDC flow.

## Table of Contents

1. [Understanding the Authentication Flow](#understanding-the-authentication-flow)
2. [Installation](#installation)
3. [Basic Setup](#basic-setup)
4. [Handling Login](#handling-login)
5. [Handling Callbacks](#handling-callbacks)
6. [Session Management](#session-management)
7. [Handling Logout](#handling-logout)
8. [Common Patterns](#common-patterns)
9. [Troubleshooting](#troubleshooting)

---

## Understanding the Authentication Flow

Before diving into code, it's important to understand the OAuth 2.0 Authorization Code Flow:

```
┌─────────┐                                           ┌──────────┐
│         │                                           │          │
│  User   │                                           │ Keycloak │
│         │                                           │  Server  │
└────┬────┘                                           └────┬─────┘
     │                                                      │
     │  1. Click "Login"                                   │
     ├──────────────────────────────────────────┐          │
     │                                           │          │
     │                                      ┌────▼─────┐    │
     │                                      │          │    │
     │                                      │   Your   │    │
     │                                      │   App    │    │
     │                                      │          │    │
     │                                      └────┬─────┘    │
     │  2. Redirect to Keycloak                 │          │
     │  ◄────────────────────────────────────────          │
     │                                                      │
     │  3. User sees Keycloak login page                   │
     ├─────────────────────────────────────────────────────►│
     │                                                      │
     │  4. User enters credentials                         │
     ├─────────────────────────────────────────────────────►│
     │                                                      │
     │  5. Keycloak redirects with auth code               │
     │◄─────────────────────────────────────────────────────┤
     │                                                      │
     │  6. App receives callback with code                 │
     ├──────────────────────────────────────────┐          │
     │                                           │          │
     │                                      ┌────▼─────┐    │
     │                                      │          │    │
     │                                      │   Your   │    │
     │                                      │   App    │    │
     │                                      │          │    │
     │                                      └────┬─────┘    │
     │                                           │          │
     │  7. App exchanges code for tokens        │          │
     │  ─────────────────────────────────────────────────────►
     │                                                      │
     │  8. Keycloak returns access token & ID token        │
     │◄─────────────────────────────────────────────────────┤
     │                                                      │
     │  9. App requests user info                          │
     │  ─────────────────────────────────────────────────────►
     │                                                      │
     │  10. Keycloak returns user profile                  │
     │◄─────────────────────────────────────────────────────┤
     │                                                      │
     │  11. App stores session & shows dashboard           │
     ├──────────────────────────────────────────┐          │
     │                                           │          │
     │                                      ┌────▼─────┐    │
     │                                      │          │    │
     │  12. User sees dashboard             │   Your   │    │
     │◄─────────────────────────────────────│   App    │    │
     │                                      │          │    │
     │                                      └──────────┘    │
     │                                                      │
```

### Key Concepts

1. **Authorization Code**: A temporary code that Keycloak returns to your app after successful login
2. **Access Token**: A JWT token that proves the user is authenticated and can access resources
3. **ID Token**: A JWT token containing user identity information
4. **Refresh Token**: A token that can be used to get new access tokens when they expire
5. **Redirect URI**: The URL where Keycloak sends the user after authentication (your callback endpoint)

---

## Installation

Install the OpenID Connect PHP library using Composer:

```bash
composer require jumbojett/openid-connect-php
```

---

## Basic Setup

### 1. Create a Configuration Class

First, create a configuration class to store your Keycloak settings:

```php
<?php

namespace Config;

class Keycloak
{
    // Keycloak server URL
    public string $serverUrl = 'https://devsso.swinnertonsolutions.com';

    // Keycloak realm name
    public string $realm = 'CLIMA';

    // Client ID (registered in Keycloak)
    public string $clientId = 'clima-frontend';

    // Client Secret (from Keycloak client credentials)
    public string $clientSecret = 'your-client-secret-here';

    // Where Keycloak should redirect after login
    public string $redirectUri = 'http://localhost:8080/auth/callback';

    // OAuth scopes to request
    public array $scopes = ['openid', 'profile', 'email'];

    // Helper methods to build endpoints
    public function getIssuerUrl(): string
    {
        return $this->serverUrl . '/realms/' . $this->realm;
    }

    public function getAuthorizationEndpoint(): string
    {
        return $this->getIssuerUrl() . '/protocol/openid-connect/auth';
    }

    public function getTokenEndpoint(): string
    {
        return $this->getIssuerUrl() . '/protocol/openid-connect/token';
    }

    public function getUserInfoEndpoint(): string
    {
        return $this->getIssuerUrl() . '/protocol/openid-connect/userinfo';
    }

    public function getLogoutEndpoint(): string
    {
        return $this->getIssuerUrl() . '/protocol/openid-connect/logout';
    }
}
```

### 2. Initialize the OIDC Client

Create a helper function to initialize the OpenID Connect client:

```php
<?php

use Jumbojett\OpenIDConnectClient;

function getOidcClient(): OpenIDConnectClient
{
    $config = new Config\Keycloak();

    // Initialize the OIDC client
    $oidc = new OpenIDConnectClient(
        $config->serverUrl . '/realms/' . $config->realm,
        $config->clientId,
        $config->clientSecret
    );

    // Set the redirect URI (where Keycloak sends users after login)
    $oidc->setRedirectURL($config->redirectUri);

    // Add scopes (what information we want to access)
    // Note: addScope() expects an array, not individual strings
    $oidc->addScope($config->scopes);

    return $oidc;
}
```

---

## Handling Login

### The Login Endpoint

Create a login endpoint that redirects users to Keycloak:

```php
<?php

// Route: /auth/login

// Check if user is already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: /dashboard');
    exit;
}

// Get OIDC client
$oidc = getOidcClient();

// This will redirect the user to Keycloak's login page
// The authenticate() method checks if there's a callback (authorization code)
// If not, it redirects to Keycloak
$oidc->authenticate();

// Code below this line won't execute because authenticate() redirects
```

### What `authenticate()` Does

The `authenticate()` method is smart:

1. **First Visit** (no auth code): Redirects to Keycloak login page
2. **Return Visit** (has auth code): Exchanges code for tokens

This is why we separate login and callback into different routes in production applications.

### Building the Authorization URL Manually (Advanced)

If you want more control, you can build the authorization URL manually:

```php
<?php

$config = new Config\Keycloak();

$authUrl = $config->getAuthorizationEndpoint() . '?' . http_build_query([
    'response_type' => 'code',
    'client_id' => $config->clientId,
    'redirect_uri' => $config->redirectUri,
    'scope' => implode(' ', $config->scopes),
    'state' => bin2hex(random_bytes(16)), // CSRF protection
    'nonce' => bin2hex(random_bytes(16))  // Replay attack protection
]);

// Store state in session for validation
$_SESSION['oauth_state'] = $state;

// Redirect to Keycloak
header('Location: ' . $authUrl);
exit;
```

---

## Handling Callbacks

### Understanding the Callback

After the user logs in at Keycloak, they are redirected back to your `redirect_uri` with query parameters:

```
http://localhost:8080/auth/callback?
  code=AUTH_CODE_HERE&
  state=STATE_VALUE&
  session_state=SESSION_ID
```

The `code` parameter is the **authorization code** that you exchange for tokens.

### The Callback Endpoint

Create a callback endpoint to handle the return from Keycloak:

```php
<?php

// Route: /auth/callback

try {
    // Get OIDC client
    $oidc = getOidcClient();

    // Complete the authentication
    // This method:
    // 1. Validates the state parameter (CSRF protection)
    // 2. Exchanges the authorization code for tokens
    // 3. Validates the tokens
    $oidc->authenticate();

    // Get user information from Keycloak
    $userInfo = $oidc->requestUserInfo();

    // Get tokens
    $accessToken = $oidc->getAccessToken();
    $idToken = $oidc->getIdToken();
    $refreshToken = $oidc->getRefreshToken(); // Optional

    // Store user info in session
    $_SESSION['logged_in'] = true;
    $_SESSION['user_info'] = $userInfo;
    $_SESSION['access_token'] = $accessToken;
    $_SESSION['id_token'] = $idToken;

    // Redirect to dashboard
    header('Location: /dashboard');
    exit;

} catch (Exception $e) {
    // Handle authentication errors
    error_log('Keycloak authentication error: ' . $e->getMessage());

    header('Location: /?error=auth_failed');
    exit;
}
```

### What Happens in the Callback

1. **Validate State**: The library checks that the `state` parameter matches what was sent
2. **Exchange Code for Tokens**: Makes a POST request to Keycloak's token endpoint
3. **Validate Tokens**: Verifies the JWT signatures and claims
4. **Fetch User Info**: Optionally fetches additional user profile data
5. **Store Session**: Your code stores the user info and tokens

### Understanding the Tokens

#### Access Token
```json
{
  "exp": 1731594955,
  "iat": 1731594655,
  "jti": "uuid-here",
  "iss": "https://devsso.swinnertonsolutions.com/realms/CLIMA",
  "sub": "user-id-uuid",
  "typ": "Bearer",
  "azp": "clima-frontend",
  "scope": "openid profile email",
  "realm_access": {
    "roles": ["user", "admin"]
  }
}
```

#### ID Token
```json
{
  "exp": 1731594955,
  "iat": 1731594655,
  "sub": "user-id-uuid",
  "name": "John Doe",
  "preferred_username": "john.doe",
  "email": "john.doe@example.com",
  "email_verified": true
}
```

#### User Info Response
```json
{
  "sub": "user-id-uuid",
  "name": "John Doe",
  "preferred_username": "john.doe",
  "given_name": "John",
  "family_name": "Doe",
  "email": "john.doe@example.com",
  "email_verified": true
}
```

---

## Session Management

### Storing User Information

Best practices for storing user data in sessions:

```php
<?php

// After successful authentication
$_SESSION['auth'] = [
    'logged_in' => true,
    'user_id' => $userInfo->sub,
    'username' => $userInfo->preferred_username,
    'email' => $userInfo->email,
    'name' => $userInfo->name,
    'access_token' => $accessToken,
    'id_token' => $idToken,
    'refresh_token' => $refreshToken,
    'token_expires_at' => time() + 300, // Access tokens typically expire in 5 minutes
];
```

### Checking Authentication Status

Create a helper function to check if a user is logged in:

```php
<?php

function isLoggedIn(): bool
{
    return isset($_SESSION['auth']['logged_in'])
        && $_SESSION['auth']['logged_in'] === true;
}

function requireAuth(): void
{
    if (!isLoggedIn()) {
        header('Location: /auth/login');
        exit;
    }
}

// Usage in protected pages
requireAuth();
```

### Token Expiration Handling

Access tokens expire quickly (typically 5 minutes). You have two options:

#### Option 1: Refresh Token (Recommended)

```php
<?php

function refreshAccessToken(): bool
{
    try {
        $oidc = getOidcClient();

        // Use the refresh token to get a new access token
        $oidc->refreshToken($_SESSION['auth']['refresh_token']);

        // Update session with new tokens
        $_SESSION['auth']['access_token'] = $oidc->getAccessToken();
        $_SESSION['auth']['id_token'] = $oidc->getIdToken();
        $_SESSION['auth']['token_expires_at'] = time() + 300;

        return true;
    } catch (Exception $e) {
        // Refresh failed, user needs to log in again
        return false;
    }
}

// Check and refresh before making API calls
if (time() >= $_SESSION['auth']['token_expires_at']) {
    if (!refreshAccessToken()) {
        // Refresh failed, redirect to login
        header('Location: /auth/login');
        exit;
    }
}
```

#### Option 2: Re-authenticate

Simply redirect users to login again when their session expires.

---

## Handling Logout

### Single Logout (Recommended)

Log the user out of both your application AND Keycloak:

```php
<?php

// Route: /auth/logout

// Get the ID token from session
$idToken = $_SESSION['auth']['id_token'] ?? null;

// Clear local session
session_destroy();

// Build Keycloak logout URL
$config = new Config\Keycloak();
$logoutUrl = $config->getLogoutEndpoint() . '?' . http_build_query([
    'post_logout_redirect_uri' => 'http://localhost:8080',
    'id_token_hint' => $idToken
]);

// Redirect to Keycloak logout
header('Location: ' . $logoutUrl);
exit;
```

### Local Logout Only

Just clear the local session without logging out of Keycloak:

```php
<?php

// Route: /auth/logout

session_destroy();

header('Location: /');
exit;
```

**Note**: With local logout only, if the user visits `/auth/login` again, they'll be automatically logged in without entering credentials (if their Keycloak session is still active).

---

## Common Patterns

### Pattern 1: Protecting Routes with Middleware

Create middleware to protect routes:

```php
<?php

class AuthMiddleware
{
    public function handle()
    {
        if (!isset($_SESSION['auth']['logged_in']) || !$_SESSION['auth']['logged_in']) {
            // Store the intended destination
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];

            header('Location: /auth/login');
            exit;
        }
    }
}

// Usage
$auth = new AuthMiddleware();
$auth->handle();
```

Then redirect to the original URL after login:

```php
<?php

// In callback handler, after successful login
$redirectTo = $_SESSION['redirect_after_login'] ?? '/dashboard';
unset($_SESSION['redirect_after_login']);

header('Location: ' . $redirectTo);
exit;
```

### Pattern 2: Role-Based Access Control (RBAC)

Extract roles from the access token:

```php
<?php

function getUserRoles(): array
{
    if (!isset($_SESSION['auth']['access_token'])) {
        return [];
    }

    // Decode JWT token (access token is a JWT)
    $tokenParts = explode('.', $_SESSION['auth']['access_token']);
    $payload = json_decode(base64_decode($tokenParts[1]), true);

    // Extract roles from realm_access or resource_access
    return $payload['realm_access']['roles'] ?? [];
}

function hasRole(string $role): bool
{
    return in_array($role, getUserRoles());
}

function requireRole(string $role): void
{
    if (!hasRole($role)) {
        http_response_code(403);
        echo 'Forbidden: Insufficient permissions';
        exit;
    }
}

// Usage
requireRole('admin');
```

### Pattern 3: Making Authenticated API Requests

Use the access token to make authenticated requests:

```php
<?php

function makeAuthenticatedRequest(string $url): array
{
    $accessToken = $_SESSION['auth']['access_token'];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 401) {
        // Token expired, try to refresh
        if (refreshAccessToken()) {
            // Retry the request
            return makeAuthenticatedRequest($url);
        }

        // Refresh failed, redirect to login
        header('Location: /auth/login');
        exit;
    }

    return json_decode($response, true);
}
```

### Pattern 4: Session Fixation Prevention

Regenerate session ID after login:

```php
<?php

// In callback handler, after successful authentication
// but before storing user info

// Regenerate session ID
session_regenerate_id(true);

// Now store user info
$_SESSION['auth'] = [
    'logged_in' => true,
    // ... rest of the data
];
```

---

## Troubleshooting

### Issue 1: "Invalid redirect_uri"

**Problem**: Keycloak rejects the login attempt.

**Solution**: Ensure the `redirect_uri` in your code EXACTLY matches what's configured in Keycloak:

```php
// In Keycloak admin console
Valid redirect URIs: http://localhost:8080/auth/callback

// In your code
$redirectUri = 'http://localhost:8080/auth/callback'; // Must match exactly
```

### Issue 2: "Session already started" or "ini_set() error"

**Problem**: The OIDC library tries to start a session, but your framework already started one.

**Solution**: Start your framework's session before initializing the OIDC client:

```php
<?php

// Start session first
session_start();

// Then initialize OIDC client
$oidc = getOidcClient();
```

### Issue 3: "SSL certificate problem"

**Problem**: Can't verify Keycloak's SSL certificate (common in development).

**Solution**: For development only, disable SSL verification:

```php
<?php

$oidc = getOidcClient();

// DEVELOPMENT ONLY - Never use in production!
$oidc->setVerifyHost(false);
$oidc->setVerifyPeer(false);
```

**Production Solution**: Install proper SSL certificates or add the CA certificate to your system's trust store.

### Issue 4: "Too many redirects" / Redirect loop

**Problem**: Login page keeps redirecting to itself.

**Solution**: Separate login initiation from callback handling:

```php
<?php

// /auth/login - Just redirect to Keycloak
if (!isset($_GET['code'])) {
    $oidc = getOidcClient();
    $oidc->authenticate(); // Redirects to Keycloak
    exit;
}

// /auth/callback - Handle the response
$oidc = getOidcClient();
$oidc->authenticate(); // Processes the auth code
// Store session and redirect to dashboard
```

**Better Solution**: Use separate routes as shown in the main guide.

### Issue 5: Token validation fails

**Problem**: "Token signature verification failed"

**Causes**:
- Clock skew between your server and Keycloak
- Incorrect client secret
- Token expired

**Solution**:

```php
<?php

// Check server time
echo "Server time: " . date('Y-m-d H:i:s') . "\n";

// Decode token to check expiration
$tokenParts = explode('.', $idToken);
$payload = json_decode(base64_decode($tokenParts[1]), true);
echo "Token expires: " . date('Y-m-d H:i:s', $payload['exp']) . "\n";

// Verify client secret matches Keycloak
```

### Issue 6: Missing user information

**Problem**: `requestUserInfo()` returns null or missing fields.

**Solution**:

1. Check that scopes are configured correctly:

```php
$oidc->addScope(['openid', 'profile', 'email']);
```

2. Verify client scopes in Keycloak admin console include the mappers you need.

3. Some information is in the ID token, not userinfo:

```php
// Decode ID token to get claims
$tokenParts = explode('.', $idToken);
$claims = json_decode(base64_decode($tokenParts[1]), true);

echo $claims['name'];
echo $claims['email'];
```

---

## Security Best Practices

### 1. Use HTTPS in Production

Always use HTTPS for your application when handling authentication:

```php
// Enforce HTTPS
if ($_SERVER['HTTPS'] !== 'on') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}
```

### 2. Validate Tokens

The library validates tokens automatically, but you can add additional checks:

```php
<?php

function validateToken(string $token): bool
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return false;
    }

    $payload = json_decode(base64_decode($parts[1]), true);

    // Check expiration
    if ($payload['exp'] < time()) {
        return false;
    }

    // Check issuer
    $config = new Config\Keycloak();
    if ($payload['iss'] !== $config->getIssuerUrl()) {
        return false;
    }

    // Check audience (client ID)
    if ($payload['azp'] !== $config->clientId) {
        return false;
    }

    return true;
}
```

### 3. Store Secrets Securely

Never hardcode secrets in your code:

```php
<?php

// Use environment variables
$clientSecret = getenv('KEYCLOAK_CLIENT_SECRET');

// Or use a secure configuration file outside web root
$config = require '/secure/path/keycloak-config.php';
```

### 4. Implement CSRF Protection

The library handles state validation, but add your own CSRF tokens for forms:

```php
<?php

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// In forms
echo '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';

// Validate on submission
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF validation failed');
}
```

### 5. Set Secure Session Cookies

```php
<?php

ini_set('session.cookie_httponly', 1); // Prevent JavaScript access
ini_set('session.cookie_secure', 1);   // HTTPS only
ini_set('session.cookie_samesite', 'Lax'); // CSRF protection
```

---

## Complete Example

Here's a complete minimal example:

### index.php
```php
<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Home</title>
</head>
<body>
    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
        <h1>Welcome, <?= htmlspecialchars($_SESSION['user_info']->name) ?>!</h1>
        <a href="/dashboard.php">Dashboard</a>
        <a href="/auth/logout.php">Logout</a>
    <?php else: ?>
        <h1>Welcome</h1>
        <a href="/auth/login.php">Login with Keycloak</a>
    <?php endif; ?>
</body>
</html>
```

### auth/login.php
```php
<?php
session_start();
require '../vendor/autoload.php';
require '../config/keycloak.php';

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    header('Location: /dashboard.php');
    exit;
}

$oidc = getOidcClient();
$oidc->authenticate();
```

### auth/callback.php
```php
<?php
session_start();
require '../vendor/autoload.php';
require '../config/keycloak.php';

try {
    $oidc = getOidcClient();
    $oidc->authenticate();

    $_SESSION['logged_in'] = true;
    $_SESSION['user_info'] = $oidc->requestUserInfo();
    $_SESSION['access_token'] = $oidc->getAccessToken();
    $_SESSION['id_token'] = $oidc->getIdToken();

    header('Location: /dashboard.php');
    exit;
} catch (Exception $e) {
    header('Location: /?error=' . urlencode($e->getMessage()));
    exit;
}
```

### auth/logout.php
```php
<?php
session_start();
require '../vendor/autoload.php';
require '../config/keycloak.php';

$idToken = $_SESSION['id_token'] ?? null;
session_destroy();

$config = new Config\Keycloak();
$logoutUrl = $config->getLogoutEndpoint() . '?' . http_build_query([
    'post_logout_redirect_uri' => 'http://localhost:8080',
    'id_token_hint' => $idToken
]);

header('Location: ' . $logoutUrl);
exit;
```

### dashboard.php
```php
<?php
session_start();

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: /auth/login.php');
    exit;
}

$user = $_SESSION['user_info'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
</head>
<body>
    <h1>Dashboard</h1>
    <p>Username: <?= htmlspecialchars($user->preferred_username) ?></p>
    <p>Email: <?= htmlspecialchars($user->email) ?></p>
    <p>Name: <?= htmlspecialchars($user->name) ?></p>
    <a href="/auth/logout.php">Logout</a>
</body>
</html>
```

---

## Additional Resources

- [Keycloak Documentation](https://www.keycloak.org/documentation)
- [OpenID Connect Specification](https://openid.net/connect/)
- [OAuth 2.0 RFC](https://tools.ietf.org/html/rfc6749)
- [jumbojett/openid-connect-php GitHub](https://github.com/jumbojett/OpenID-Connect-PHP)
- [JWT.io - JWT Debugger](https://jwt.io/)

---

## Summary

**Key Takeaways:**

1. ✅ Use the `jumbojett/openid-connect-php` library for easy OIDC integration
2. ✅ Separate login initiation from callback handling
3. ✅ Store tokens and user info securely in sessions
4. ✅ Always use HTTPS in production
5. ✅ Implement proper logout (single logout recommended)
6. ✅ Handle token expiration with refresh tokens
7. ✅ Validate redirect URIs match Keycloak configuration
8. ✅ Start sessions before initializing OIDC client
9. ✅ Use role-based access control when needed
10. ✅ Never hardcode secrets in your code

Happy coding! 🚀
