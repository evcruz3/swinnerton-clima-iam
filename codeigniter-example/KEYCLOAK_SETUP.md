# Keycloak Setup Guide for CLIMA

Step-by-step guide to configure Keycloak for the CLIMA CodeIgniter application.

## Prerequisites

- Access to Keycloak Admin Console at https://devsso.swinnertonsolutions.com/admin
- Admin credentials for Keycloak

## Step 1: Create or Verify Realm

1. Login to Keycloak Admin Console
2. If "CLIMA" realm doesn't exist:
   - Click the dropdown in the top left (shows "master")
   - Click "Create Realm"
   - Enter "CLIMA" as the realm name
   - Click "Create"

## Step 2: Create Client

1. Navigate to **Clients** in the left menu
2. Click **Create client** button

### General Settings:
- **Client type:** OpenID Connect
- **Client ID:** `clima-frontend`
- Click **Next**

### Capability config:
- **Client authentication:** ON (this enables client secret)
- **Authorization:** OFF
- **Authentication flow:** Check these:
  - ✅ Standard flow
  - ✅ Direct access grants
- Click **Next**

### Login settings:
- **Root URL:** `http://localhost:8080`
- **Home URL:** `http://localhost:8080`
- **Valid redirect URIs:**
  - `http://localhost:8080/*`
  - `http://localhost:8080/auth/callback`
- **Valid post logout redirect URIs:**
  - `http://localhost:8080/*`
- **Web origins:**
  - `http://localhost:8080`
  - `*` (for development only)
- Click **Save**

## Step 3: Get Client Secret

1. Go to the **Credentials** tab of the `clima-frontend` client
2. Copy the **Client Secret**
3. Update `app/Config/Keycloak.php` with this secret:

```php
public string $clientSecret = 'YOUR-CLIENT-SECRET-HERE';
```

## Step 4: Configure Client Scopes (Optional but Recommended)

1. Go to **Client scopes** in the left menu
2. Verify that these default scopes exist:
   - `openid`
   - `profile`
   - `email`

These are automatically added to clients and include:
- `openid`: Basic OpenID Connect claims
- `profile`: Name, username, etc.
- `email`: Email address and verification status

## Step 5: Create Test Users

### Create a User:

1. Navigate to **Users** in the left menu
2. Click **Add user**
3. Fill in the form:
   - **Username:** testuser
   - **Email:** testuser@example.com
   - **First name:** Test
   - **Last name:** User
   - **Email verified:** ON
4. Click **Create**

### Set Password:

1. After creating the user, go to the **Credentials** tab
2. Click **Set password**
3. Enter a password (e.g., `password123`)
4. Set **Temporary:** OFF (so user doesn't have to change it)
5. Click **Save**
6. Click **Save password** in the confirmation dialog

### Create Multiple Users (Optional):

Repeat the above steps to create more test users:
- testuser2@example.com
- admin@example.com
- etc.

## Step 6: Configure Realm Settings (Optional)

### Login Settings:

1. Go to **Realm settings** → **Login** tab
2. Configure as needed:
   - **User registration:** OFF (unless you want self-registration)
   - **Forgot password:** ON
   - **Remember me:** ON
   - **Email as username:** OFF

### Tokens:

1. Go to **Realm settings** → **Tokens** tab
2. Adjust token lifespans if needed (defaults are usually fine):
   - **Access token lifespan:** 5 minutes
   - **SSO session idle:** 30 minutes
   - **SSO session max:** 10 hours

## Step 7: Test Configuration

1. Start your CodeIgniter application:
   ```bash
   cd codeigniter-example
   composer install
   cp .env.example .env
   php spark serve
   ```

2. Visit http://localhost:8080

3. Click "Login with Keycloak"

4. You should be redirected to Keycloak login page

5. Login with your test user credentials

6. You should be redirected back to the dashboard

## Common Configuration Issues

### Issue: "Invalid redirect_uri"

**Solution:** Make sure redirect URIs in Keycloak match exactly:
- Must include the protocol (`http://`)
- Must match the port (`:8080`)
- Can use wildcard: `http://localhost:8080/*`

### Issue: "Client authentication failed"

**Solution:**
- Verify "Client authentication" is **ON**
- Verify the client secret in your config matches Keycloak

### Issue: "Invalid parameter: redirect_uri"

**Solution:**
- Check `app.baseURL` in `.env` matches your redirect URIs
- Ensure no trailing slash in base URL

## Production Configuration

When deploying to production:

### Update URLs:

1. In Keycloak client settings, add production URLs:
   - **Root URL:** `https://your-domain.com`
   - **Valid redirect URIs:** `https://your-domain.com/*`
   - **Web origins:** `https://your-domain.com`

2. In `app/Config/Keycloak.php`:
   ```php
   public string $redirectUri = 'https://your-domain.com/auth/callback';
   ```

3. In `.env`:
   ```ini
   app.baseURL = 'https://your-domain.com'
   ```

### Security:

- Remove wildcard (`*`) from web origins
- Use specific redirect URIs (not wildcards)
- Enable HTTPS strict mode
- Set strong session settings
- Regularly rotate client secrets

## Advanced: Role-Based Access Control

### Create Roles:

1. Go to **Realm roles** in the left menu
2. Click **Create role**
3. Create roles like:
   - `admin`
   - `user`
   - `manager`

### Assign Roles to Users:

1. Go to **Users** → select a user
2. Go to **Role mapping** tab
3. Click **Assign role**
4. Select roles to assign
5. Click **Assign**

### Add Roles to Token:

1. Go to **Client scopes** → **roles**
2. Go to **Mappers** tab
3. Verify "realm roles" mapper exists
4. This adds roles to the access token

### Access Roles in CodeIgniter:

```php
// In your controller
$session = session();
$accessToken = $session->get('access_token');

// Decode JWT token
$tokenParts = explode('.', $accessToken);
$payload = json_decode(base64_decode($tokenParts[1]));

// Get roles
$roles = $payload->realm_access->roles ?? [];

// Check role
if (in_array('admin', $roles)) {
    // User has admin role
}
```

## Testing Checklist

- [ ] Login works
- [ ] User info displays correctly
- [ ] Logout works
- [ ] Session persists after page refresh
- [ ] Invalid credentials are rejected
- [ ] Token expiration is handled
- [ ] Redirect after login works

## Useful Keycloak Admin Tasks

### View Active Sessions:
- Go to **Sessions** → **Active sessions**

### View Events (Audit Log):
- Go to **Events** → **Login events**
- Go to **Events** → **Admin events**

### Export/Backup Realm:
- Go to **Realm settings** → **Action** → **Partial export**

### Import Users:
- Go to **Users** → **Import users** (JSON format)

## Resources

- [Keycloak Server Admin Guide](https://www.keycloak.org/docs/latest/server_admin/)
- [OpenID Connect Endpoints](https://devsso.swinnertonsolutions.com/realms/CLIMA/.well-known/openid-configuration)
- [Keycloak REST API](https://www.keycloak.org/docs-api/latest/rest-api/)
