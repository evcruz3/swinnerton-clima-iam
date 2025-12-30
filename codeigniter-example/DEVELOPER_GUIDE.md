# Developer Guide: Integrating Keycloak with CodeIgniter 3

This guide explains how to integrate Keycloak authentication into your CodeIgniter 3 application using OpenID Connect (OIDC). We'll use the `jumbojett/openid-connect-php` library, which simplifies the OAuth 2.0 / OIDC flow.

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

### 1. Create a Configuration File

Create a configuration array in `application/config/keycloak.php`:

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Keycloak Configuration
|--------------------------------------------------------------------------
*/

// Keycloak server URL
$config['keycloak_server_url'] = 'https://devsso.swinnertonsolutions.com';

// Keycloak realm name
$config['keycloak_realm'] = 'CLIMA';

// Client ID (registered in Keycloak)
$config['keycloak_client_id'] = 'clima-frontend';

// Client Secret (from Keycloak client credentials)
$config['keycloak_client_secret'] = 'your-client-secret-here';

// Where Keycloak should redirect after login
$config['keycloak_redirect_uri'] = 'http://localhost:8080/auth/callback';

// OAuth scopes to request
$config['keycloak_scopes'] = ['openid', 'profile', 'email'];
```

### 2. Create the Keycloak Authentication Library

Create a library in `application/libraries/Keycloak_auth.php`:

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Jumbojett\OpenIDConnectClient;

class Keycloak_auth
{
    protected $CI;
    protected $oidc;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->config('keycloak');

        $this->initialize_oidc();
    }

    protected function initialize_oidc()
    {
        $server_url = $this->CI->config->item('keycloak_server_url');
        $realm = $this->CI->config->item('keycloak_realm');
        $client_id = $this->CI->config->item('keycloak_client_id');
        $client_secret = $this->CI->config->item('keycloak_client_secret');
        $redirect_uri = $this->CI->config->item('keycloak_redirect_uri');
        $scopes = $this->CI->config->item('keycloak_scopes');

        $issuer_url = $server_url . '/realms/' . $realm;

        $this->oidc = new OpenIDConnectClient(
            $issuer_url,
            $client_id,
            $client_secret
        );

        $this->oidc->setRedirectURL($redirect_uri);
        $this->oidc->addScope($scopes);
    }

    public function login()
    {
        try {
            $this->oidc->authenticate();
        } catch (Exception $e) {
            log_message('error', 'Keycloak authentication error: ' . $e->getMessage());
            throw $e;
        }
    }

    // ... other methods (we'll add these later)
}
```

---

## Handling Login

### The Login Endpoint

Create an Auth controller in `application/controllers/Auth.php`:

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('keycloak_auth');
    }

    public function login()
    {
        // Check if user is already logged in
        if ($this->session->userdata('logged_in')) {
            redirect('dashboard');
            return;
        }

        try {
            // This will redirect the user to Keycloak's login page
            $this->keycloak_auth->login();
        } catch (Exception $e) {
            log_message('error', 'Keycloak login error: ' . $e->getMessage());
            $this->session->set_flashdata('error', 'Login failed');
            redirect('/');
        }
    }
}
```

### What `authenticate()` Does

The `authenticate()` method is smart:

1. **First Visit** (no auth code): Redirects to Keycloak login page
2. **Return Visit** (has auth code): Exchanges code for tokens

This is why we separate login and callback into different routes in production applications.

### Building the Authorization URL Manually (Advanced)

If you want more control, you can build the authorization URL manually in your controller:

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends MY_Controller
{
    public function manual_login()
    {
        $this->load->config('keycloak');

        $server_url = $this->config->item('keycloak_server_url');
        $realm = $this->config->item('keycloak_realm');
        $client_id = $this->config->item('keycloak_client_id');
        $redirect_uri = $this->config->item('keycloak_redirect_uri');
        $scopes = $this->config->item('keycloak_scopes');

        $state = bin2hex(random_bytes(16));
        $nonce = bin2hex(random_bytes(16));

        // Store state in session for validation
        $this->session->set_userdata('oauth_state', $state);
        $this->session->set_userdata('oauth_nonce', $nonce);

        $auth_endpoint = $server_url . '/realms/' . $realm . '/protocol/openid-connect/auth';

        $authUrl = $auth_endpoint . '?' . http_build_query([
            'response_type' => 'code',
            'client_id' => $client_id,
            'redirect_uri' => $redirect_uri,
            'scope' => implode(' ', $scopes),
            'state' => $state,
            'nonce' => $nonce
        ]);

        // Redirect to Keycloak
        redirect($authUrl);
    }
}
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

Add the callback method to your Auth controller:

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends MY_Controller
{
    public function callback()
    {
        try {
            // Complete authentication and get user info
            $user_info = $this->keycloak_auth->handle_callback();

            // Redirect to dashboard
            redirect('dashboard');

        } catch (Exception $e) {
            log_message('error', 'Keycloak callback error: ' . $e->getMessage());
            $this->session->set_flashdata('error', 'Authentication failed');
            redirect('/');
        }
    }
}
```

In your `Keycloak_auth` library, add the `handle_callback()` method:

```php
public function handle_callback()
{
    try {
        // Complete the authentication
        $this->oidc->authenticate();

        // Get tokens first
        $access_token = $this->oidc->getAccessToken();
        $id_token = $this->oidc->getIdToken();

        // Get user information from Keycloak
        $user_info = $this->oidc->requestUserInfo();

        // Store in session
        $this->CI->session->set_userdata(array(
            'logged_in' => TRUE,
            'user_info' => $user_info,
            'access_token' => $access_token,
            'id_token' => $id_token
        ));

        return $user_info;

    } catch (Exception $e) {
        log_message('error', 'Keycloak callback error: ' . $e->getMessage());
        throw $e;
    }
}
```

### Helper Methods

The Keycloak_auth library also includes these convenient helper methods:

```php
public function is_authenticated()
{
    return $this->CI->session->userdata('logged_in') === TRUE;
}

public function get_user_info()
{
    return $this->CI->session->userdata('user_info');
}

public function get_access_token()
{
    return $this->CI->session->userdata('access_token');
}

public function get_id_token()
{
    return $this->CI->session->userdata('id_token');
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

Best practices for storing user data in CodeIgniter 3 sessions:

```php
<?php
// application/libraries/Keycloak_auth.php - handle_callback method

$CI =& get_instance();

// After successful authentication
$CI->session->set_userdata(array(
    'logged_in' => true,
    'user_id' => $userInfo->sub,
    'username' => $userInfo->preferred_username,
    'email' => $userInfo->email,
    'name' => $userInfo->name,
    'access_token' => $accessToken,
    'id_token' => $idToken,
    'refresh_token' => $refreshToken,
    'token_expires_at' => time() + 300 // Access tokens typically expire in 5 minutes
));
```

### Checking Authentication Status

In CodeIgniter 3, use the MY_Controller methods to check authentication:

```php
<?php
// application/core/MY_Controller.php

class MY_Controller extends CI_Controller
{
    protected $public_controllers = array('auth', 'home');

    public function __construct()
    {
        parent::__construct();
    }

    protected function is_authenticated()
    {
        return $this->session->userdata('logged_in') === TRUE;
    }

    protected function get_user_info()
    {
        return $this->session->userdata('user_info');
    }

    protected function require_auth()
    {
        if (!$this->is_authenticated()) {
            $this->session->set_flashdata('error', 'Please login to access this page');
            redirect('auth/login');
        }
    }

    public function needs_authentication()
    {
        $controller = $this->router->fetch_class();
        return !in_array(strtolower($controller), $this->public_controllers);
    }
}

// Usage in a controller
class Dashboard extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->require_auth();  // Protect this controller
    }
}
```

### Token Expiration Handling

Access tokens expire quickly (typically 5 minutes). You have two options:

#### Option 1: Refresh Token (Optional Enhancement)

> **Note:** Token refresh is not implemented in the example code but can be added as an enhancement.

```php
<?php
// application/libraries/Keycloak_auth.php

// Note: This method is an optional enhancement, not included in the base example

public function refresh_access_token()
{
    $CI =& get_instance();

    try {
        $refreshToken = $CI->session->userdata('refresh_token');

        // Use the refresh token to get a new access token
        $this->oidc->refreshToken($refreshToken);

        // Update session with new tokens
        $CI->session->set_userdata([
            'access_token' => $this->oidc->getAccessToken(),
            'id_token' => $this->oidc->getIdToken(),
            'token_expires_at' => time() + 300
        ]);

        return true;
    } catch (Exception $e) {
        // Refresh failed, user needs to log in again
        return false;
    }
}

// Usage in MY_Controller
protected function check_token_expiration()
{
    if (time() >= $this->session->userdata('token_expires_at')) {
        $this->load->library('keycloak_auth');
        if (!$this->keycloak_auth->refresh_access_token()) {
            redirect('auth/login');
        }
    }
}
```

#### Option 2: Re-authenticate (Default in Example)

Simply redirect users to login again when their session expires. This is the approach used in the example implementation.

---

## Handling Logout

### Single Logout (Recommended)

Log the user out of both your application AND Keycloak:

```php
<?php
// application/controllers/Auth.php

public function logout()
{
    // Get Keycloak logout URL and destroy session
    $logout_url = $this->keycloak_auth->logout();

    // Build full logout URL with redirect
    $logout_url .= '?' . http_build_query([
        'post_logout_redirect_uri' => base_url(),
        'id_token_hint' => $this->session->userdata('id_token')
    ]);

    // Redirect to Keycloak logout
    redirect($logout_url);
}
```

In the Keycloak_auth library:

```php
<?php
// application/libraries/Keycloak_auth.php

public function logout()
{
    $server_url = $this->CI->config->item('keycloak_server_url');
    $realm = $this->CI->config->item('keycloak_realm');

    // Destroy session
    $this->CI->session->sess_destroy();

    // Return Keycloak logout URL
    return $server_url . '/realms/' . $realm . '/protocol/openid-connect/logout';
}
```

### Local Logout Only

Just clear the local session without logging out of Keycloak:

```php
<?php
// application/controllers/Auth.php

public function logout()
{
    // Clear local session only
    $this->session->sess_destroy();

    // Redirect to home
    redirect('/');
}
```

**Note**: With local logout only, if the user visits `/auth/login` again, they'll be automatically logged in without entering credentials (if their Keycloak session is still active).

---

## Common Patterns

### Pattern 1: Protecting Routes with Hooks

Use CodeIgniter 3's hook system to protect routes automatically:

```php
<?php
// application/hooks/Auth_check.php

class Auth_check
{
    public function check_authentication()
    {
        $CI =& get_instance();

        // Get the current controller
        $controller = $CI->router->fetch_class();

        // Skip public controllers
        $publicControllers = array('auth', 'home');
        if (in_array(strtolower($controller), $publicControllers)) {
            return;
        }

        // Check if user is logged in
        if (!$CI->session->userdata('logged_in')) {
            // Store the intended destination
            $CI->session->set_userdata('redirect_url', current_url());

            // Set error message
            $CI->session->set_flashdata('error', 'Please login to access this page');

            // Redirect to login
            redirect('auth/login');
        }
    }
}
```

You can optionally redirect to the original URL after login:

```php
<?php
// application/controllers/Auth.php - in callback method

public function callback()
{
    $this->load->library('keycloak_auth');

    try {
        $userInfo = $this->keycloak_auth->handle_callback();

        // Optional: Get intended destination (if using the redirect pattern)
        $redirectTo = $this->session->userdata('redirect_url');
        if ($redirectTo) {
            $this->session->unset_userdata('redirect_url');
            redirect($redirectTo);
        }

        // Default redirect
        redirect('dashboard');
    } catch (Exception $e) {
        $this->session->set_flashdata('error', $e->getMessage());
        redirect('/');
    }
}
```

> **Note:** The example implementation doesn't use the redirect-after-login pattern and always redirects to 'dashboard'. The code above shows how you could implement it if needed.

### Pattern 2: Role-Based Access Control (RBAC)

> **Note:** This pattern is not implemented in the example code but can be added as an enhancement.

Extract roles from the access token in MY_Controller:

```php
<?php
// application/core/MY_Controller.php

class MY_Controller extends CI_Controller
{
    // Note: These methods are optional enhancements, not included in the base example

    protected function get_user_roles()
    {
        $accessToken = $this->session->userdata('access_token');
        if (!$accessToken) {
            return array();
        }

        // Decode JWT token (access token is a JWT)
        $tokenParts = explode('.', $accessToken);
        $payload = json_decode(base64_decode($tokenParts[1]), true);

        // Extract roles from realm_access or resource_access
        return isset($payload['realm_access']['roles']) ? $payload['realm_access']['roles'] : array();
    }

    protected function has_role($role)
    {
        return in_array($role, $this->get_user_roles());
    }

    protected function require_role($role)
    {
        if (!$this->has_role($role)) {
            show_error('Forbidden: Insufficient permissions', 403, 'Access Denied');
        }
    }
}

// Usage in a controller
class Admin extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->require_auth();
        $this->require_role('admin');
    }
}
```

### Pattern 3: Making Authenticated API Requests

Use the access token to make authenticated requests in a controller:

```php
<?php
// application/controllers/Api.php or within MY_Controller

class Api extends MY_Controller
{
    public function make_authenticated_request($url)
    {
        $accessToken = $this->session->userdata('access_token');

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 401) {
            // Token expired, try to refresh
            $this->load->library('keycloak_auth');
            if ($this->keycloak_auth->refresh_access_token()) {
                // Retry the request
                return $this->make_authenticated_request($url);
            }

            // Refresh failed, redirect to login
            redirect('auth/login');
        }

        return json_decode($response, true);
    }
}
```

### Pattern 4: Session Fixation Prevention

Regenerate session ID after login in CodeIgniter 3:

```php
<?php
// application/libraries/Keycloak_auth.php

public function handle_callback()
{
    $CI =& get_instance();

    $this->oidc->authenticate();

    // Regenerate session ID for security
    $CI->session->sess_regenerate(TRUE);

    // Now store user info
    $userInfo = $this->oidc->requestUserInfo();
    $CI->session->set_userdata(array(
        'logged_in' => true,
        'user_info' => $userInfo,
        'access_token' => $this->oidc->getAccessToken(),
        'id_token' => $this->oidc->getIdToken()
    ));

    return $userInfo;
}
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

**Problem**: The OIDC library tries to start a session, but CodeIgniter already started one.

**Solution**: CodeIgniter 3 automatically handles session initialization when you autoload the session library. Ensure it's configured correctly in `application/config/autoload.php`:

```php
<?php
// application/config/autoload.php

$autoload['libraries'] = array('session');
$autoload['helper'] = array('url');
$autoload['config'] = array('keycloak');
```

The Keycloak_auth library will use CodeIgniter's existing session, so no additional configuration is needed.

### Issue 3: "SSL certificate problem"

**Problem**: Can't verify Keycloak's SSL certificate (common in development).

**Solution**: For development only, disable SSL verification in the Keycloak_auth library:

```php
<?php
// application/libraries/Keycloak_auth.php

public function __construct()
{
    $CI =& get_instance();

    // ... existing configuration ...

    // DEVELOPMENT ONLY - Never use in production!
    if (ENVIRONMENT === 'development') {
        $this->oidc->setVerifyHost(false);
        $this->oidc->setVerifyPeer(false);
    }
}
```

**Production Solution**: Install proper SSL certificates or add the CA certificate to your system's trust store.

### Issue 4: "Too many redirects" / Redirect loop

**Problem**: Login page keeps redirecting to itself.

**Solution**: Ensure you have separate controllers/methods for login initiation and callback handling:

```php
<?php
// application/controllers/Auth.php

class Auth extends MY_Controller
{
    // /auth/login - Redirects to Keycloak
    public function login()
    {
        $this->load->library('keycloak_auth');
        $this->keycloak_auth->login();
    }

    // /auth/callback - Handles the response
    public function callback()
    {
        $this->load->library('keycloak_auth');
        try {
            $this->keycloak_auth->handle_callback();
            redirect('dashboard');
        } catch (Exception $e) {
            $this->session->set_flashdata('error', $e->getMessage());
            redirect('/');
        }
    }
}
```

Also ensure the Auth controller is in your `$public_controllers` array in MY_Controller.

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

Always use HTTPS for your application when handling authentication. Configure this in your `.htaccess` or web server configuration:

```apache
# .htaccess - Force HTTPS
RewriteEngine On
RewriteCond %{HTTPS} !=on
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

Or in CodeIgniter, update your `config.php`:

```php
<?php
// application/config/config.php

// Force HTTPS URLs
$config['base_url'] = 'https://yourdomain.com/';
```

### 2. Validate Tokens

The library validates tokens automatically, but you can add additional checks:

```php
<?php
// application/libraries/Keycloak_auth.php or application/helpers/keycloak_helper.php

function validateToken($token)
{
    $CI =& get_instance();

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
    $serverUrl = $CI->config->item('keycloak_server_url');
    $realm = $CI->config->item('keycloak_realm');
    $expectedIssuer = $serverUrl . '/realms/' . $realm;

    if ($payload['iss'] !== $expectedIssuer) {
        return false;
    }

    // Check audience (client ID)
    $clientId = $CI->config->item('keycloak_client_id');
    if ($payload['azp'] !== $clientId) {
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

CodeIgniter 3 has built-in CSRF protection. Enable it in your config:

```php
<?php
// application/config/config.php

$config['csrf_protection'] = TRUE;
$config['csrf_token_name'] = 'csrf_token';
$config['csrf_cookie_name'] = 'csrf_cookie';
$config['csrf_expire'] = 7200;
$config['csrf_regenerate'] = TRUE;
$config['csrf_exclude_uris'] = array();
```

In your forms, use CodeIgniter's CSRF helper:

```php
<!-- In views -->
<form method="post">
    <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
    <!-- rest of form -->
</form>
```

CodeIgniter automatically validates CSRF tokens on form submissions.

### 5. Set Secure Session Cookies

Configure secure session cookies in CodeIgniter 3:

```php
<?php
// application/config/config.php

$config['cookie_httponly'] = TRUE;  // Prevent JavaScript access
$config['cookie_secure'] = TRUE;    // HTTPS only (set to FALSE for local dev)
$config['cookie_samesite'] = 'Lax'; // CSRF protection
$config['sess_cookie_name'] = 'ci_session';
$config['sess_expiration'] = 7200;
$config['sess_save_path'] = APPPATH . 'sessions/';
```

---

## Complete Example

Here's a complete minimal CodeIgniter 3 example:

### application/controllers/Home.php
```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends MY_Controller
{
    public function index()
    {
        $this->load->view('welcome');
    }
}
```

### application/controllers/Auth.php
```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends MY_Controller
{
    public function login()
    {
        if ($this->is_authenticated()) {
            redirect('dashboard');
        }

        $this->load->library('keycloak_auth');
        $this->keycloak_auth->login();
    }

    public function callback()
    {
        $this->load->library('keycloak_auth');

        try {
            $userInfo = $this->keycloak_auth->handle_callback();
            redirect('dashboard');
        } catch (Exception $e) {
            $this->session->set_flashdata('error', $e->getMessage());
            redirect('/');
        }
    }

    public function logout()
    {
        $this->load->library('keycloak_auth');
        $this->keycloak_auth->logout();
    }
}
```

### application/controllers/Dashboard.php
```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->require_auth();
    }

    public function index()
    {
        $data['user'] = $this->get_user_info();
        $this->load->view('dashboard', $data);
    }
}
```

### application/views/welcome.php
```php
<!DOCTYPE html>
<html>
<head>
    <title>Home</title>
</head>
<body>
    <h1>Welcome to CLIMA</h1>
    <p>Please login with your Keycloak account to continue</p>

    <?php if ($this->session->flashdata('error')): ?>
        <div class="error">
            <?= htmlspecialchars($this->session->flashdata('error'), ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <a href="<?= base_url('auth/login') ?>">Login with Keycloak</a>
</body>
</html>
```

### application/views/dashboard.php
```php
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
</head>
<body>
    <h1>Dashboard</h1>
    <p>Username: <?= htmlspecialchars($user->preferred_username ?? 'N/A') ?></p>
    <p>Email: <?= htmlspecialchars($user->email ?? 'N/A') ?></p>
    <p>Name: <?= htmlspecialchars($user->name ?? 'N/A') ?></p>
    <a href="<?= base_url('auth/logout') ?>">Logout</a>
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
