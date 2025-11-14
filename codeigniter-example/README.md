# CodeIgniter 4 + Keycloak OIDC Integration Example

A simple example demonstrating how to integrate Keycloak authentication with CodeIgniter 4 using OpenID Connect (OIDC).

## Prerequisites

- PHP 8.1 or higher
- Composer
- A running Keycloak server (https://devsso.swinnertonsolutions.com)
- Keycloak realm and client configured

## Features

- Simple login/logout with Keycloak
- OpenID Connect (OIDC) integration using `jumbojett/openid-connect-php`
- Session-based user management
- User profile display from Keycloak
- Clean, modern UI

## Installation

### 1. Install Dependencies

```bash
cd codeigniter-example
composer install
```

### 2. Configure Environment

Copy the `.env` file from CodeIgniter framework:

```bash
cp vendor/codeigniter4/framework/app/.env .env
```

Edit `.env` and set:

```ini
CI_ENVIRONMENT = development

app.baseURL = 'http://localhost:8080'

# Session
app.sessionDriver = 'CodeIgniter\Session\Handlers\FileHandler'
app.sessionCookieName = 'ci_session'
app.sessionExpiration = 7200
app.sessionSavePath = writable/session
app.sessionMatchIP = false
app.sessionTimeToUpdate = 300
app.sessionRegenerateDestroy = false
```

### 3. Configure Keycloak

Edit `app/Config/Keycloak.php` if needed (already configured for CLIMA realm):

```php
public string $serverUrl = 'https://devsso.swinnertonsolutions.com';
public string $realm = 'CLIMA';
public string $clientId = 'clima-frontend';
public string $clientSecret = 'gQig4u1mLON1CEDxhXQwTI1CaWhCGA8v';
public string $redirectUri = 'http://localhost:8080/auth/callback';
```

### 4. Setup Keycloak Client in Admin Console

Login to Keycloak Admin Console at `https://devsso.swinnertonsolutions.com/admin`

#### Configure the Client:

1. Go to **Realm: CLIMA** → **Clients** → **clima-frontend**

2. **Settings tab:**
   - Client authentication: **ON**
   - Authorization: **OFF** (unless you need fine-grained authorization)
   - Authentication flow:
     - ✅ Standard flow
     - ✅ Direct access grants
   - Valid redirect URIs:
     - `http://localhost:8080/*`
     - `http://localhost:8080/auth/callback`
   - Valid post logout redirect URIs:
     - `http://localhost:8080/*`
   - Web origins:
     - `http://localhost:8080`

3. **Credentials tab:**
   - Copy the Client Secret and update `app/Config/Keycloak.php` if different

4. Click **Save**

### 5. Create a Test User (if needed)

1. Go to **Users** → **Add user**
2. Fill in username, email, first name, last name
3. Click **Create**
4. Go to **Credentials** tab
5. Set a password (uncheck "Temporary")

### 6. Run the Application

```bash
php spark serve
```

Visit http://localhost:8080

## Project Structure

```
codeigniter-example/
├── app/
│   ├── Config/
│   │   ├── Keycloak.php      # Keycloak configuration
│   │   └── Routes.php         # Application routes
│   ├── Controllers/
│   │   ├── Auth.php           # Authentication controller
│   │   ├── Dashboard.php      # Protected dashboard
│   │   └── Home.php           # Public home page
│   └── Views/
│       ├── welcome.php        # Login page
│       └── dashboard.php      # User dashboard
├── composer.json
└── README.md
```

## How It Works

### 1. Login Flow

```
User clicks "Login"
  → Redirects to Keycloak login page
  → User enters credentials in Keycloak
  → Keycloak redirects back with authorization code
  → App exchanges code for tokens
  → App fetches user info
  → User info stored in session
  → Redirects to dashboard
```

### 2. Session Management

User information is stored in CodeIgniter sessions:
- `logged_in`: Boolean flag
- `user_info`: User profile data from Keycloak
- `access_token`: OAuth2 access token
- `id_token`: OpenID Connect ID token

### 3. Logout Flow

```
User clicks "Logout"
  → App destroys local session
  → Redirects to Keycloak logout endpoint
  → Keycloak logs out user
  → Redirects back to home page
```

## Key Files Explained

### app/Config/Keycloak.php

Configuration class containing all Keycloak settings:
- Server URL
- Realm name
- Client ID and secret
- Redirect URIs
- Helper methods for endpoint URLs

### app/Controllers/Auth.php

Handles authentication flow:
- `login()`: Initiates OIDC authentication
- `callback()`: Handles OAuth callback
- `logout()`: Logs out from Keycloak
- `checkAuth()`: Middleware-like method to protect routes

### app/Controllers/Dashboard.php

Protected page that requires authentication. Displays user information from Keycloak.

## Using the OpenID Connect Library

The `jumbojett/openid-connect-php` library simplifies OIDC integration:

```php
// Initialize
$oidc = new OpenIDConnectClient($providerUrl, $clientId, $clientSecret);
$oidc->setRedirectURL($redirectUri);
$oidc->addScope('openid');
$oidc->addScope('profile');
$oidc->addScope('email');

// Authenticate
$oidc->authenticate();

// Get user info
$userInfo = $oidc->requestUserInfo();

// Get tokens
$accessToken = $oidc->getAccessToken();
$idToken = $oidc->getIdToken();
```

## Protecting Routes

To protect a route, add a check in your controller:

```php
public function protectedPage()
{
    $session = session();

    if (!$session->get('logged_in')) {
        return redirect()->to('/auth/login');
    }

    // Your protected code here
}
```

Or create a filter for better reusability.

## Troubleshooting

### "Invalid redirect_uri"

Make sure the redirect URI in your Keycloak client configuration matches exactly:
- `http://localhost:8080/auth/callback`

### "Client authentication failed"

Check that:
- Client ID is correct
- Client Secret is correct
- Client authentication is enabled in Keycloak

### "SSL certificate problem"

In development, you can disable SSL verification (NOT for production):

```php
$this->oidc->setVerifyHost(false);
$this->oidc->setVerifyPeer(false);
```

### Session not persisting

Make sure the `writable/session` directory exists and is writable:

```bash
mkdir -p writable/session
chmod 777 writable/session
```

## Security Notes

1. **Never commit secrets**: Add `.env` to `.gitignore`
2. **Use HTTPS in production**: Always use HTTPS for OAuth flows
3. **Validate tokens**: The library validates tokens automatically
4. **Secure sessions**: Use secure session settings in production
5. **CSRF protection**: CodeIgniter has built-in CSRF protection

## Production Checklist

- [ ] Use HTTPS for all URLs
- [ ] Set `CI_ENVIRONMENT = production` in `.env`
- [ ] Update redirect URIs in Keycloak to production URLs
- [ ] Use strong, unique client secrets
- [ ] Enable session encryption
- [ ] Set proper CORS policies
- [ ] Enable rate limiting
- [ ] Monitor authentication logs

## Extending This Example

### Add Role-Based Access Control

Check user roles from Keycloak:

```php
$accessToken = $session->get('access_token');
$tokenParts = explode('.', $accessToken);
$payload = json_decode(base64_decode($tokenParts[1]));
$roles = $payload->realm_access->roles ?? [];

if (in_array('admin', $roles)) {
    // Admin access
}
```

### Refresh Tokens

Implement token refresh for long-running sessions:

```php
$refreshToken = $oidc->getRefreshToken();
// Store and use for token refresh
```

### Fetch Additional User Attributes

Configure custom attributes in Keycloak and they'll appear in `userInfo`.

## Resources

- [Keycloak Documentation](https://www.keycloak.org/documentation)
- [OpenID Connect PHP Library](https://github.com/jumbojett/OpenID-Connect-PHP)
- [CodeIgniter 4 Documentation](https://codeigniter.com/user_guide/)
- [OAuth 2.0 Specification](https://oauth.net/2/)
- [OpenID Connect Specification](https://openid.net/connect/)

## License

This is an example project for educational purposes.
