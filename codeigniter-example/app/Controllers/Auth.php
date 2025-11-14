<?php

namespace App\Controllers;

use Config\Keycloak;
use Jumbojett\OpenIDConnectClient;

class Auth extends BaseController
{
    private OpenIDConnectClient $oidc;
    private Keycloak $config;

    public function __construct()
    {
        $this->config = config('Keycloak');

        // Start CodeIgniter session before OIDC client tries to start its own
        if (session_status() === PHP_SESSION_NONE) {
            session();
        }
    }

    /**
     * Initialize OIDC client (called when needed)
     */
    private function getOidcClient(): OpenIDConnectClient
    {
        if (!isset($this->oidc)) {
            // Initialize OpenID Connect Client
            $this->oidc = new OpenIDConnectClient(
                $this->config->serverUrl . '/realms/' . $this->config->realm,
                $this->config->clientId,
                $this->config->clientSecret
            );

            // Set the redirect URI
            $this->oidc->setRedirectURL($this->config->redirectUri);

            // Add scopes (addScope expects an array)
            $this->oidc->addScope($this->config->scopes);
        }

        return $this->oidc;
    }

    /**
     * Initiate login process
     */
    public function login()
    {
        $session = session();

        // Check if already logged in
        if ($session->get('logged_in')) {
            return redirect()->to('/dashboard');
        }

        // Get OIDC client and redirect to Keycloak for authentication
        $oidc = $this->getOidcClient();
        $oidc->authenticate();

        // Note: authenticate() will redirect to Keycloak, so code below won't execute
        // The callback will be handled by the callback() method
    }

    /**
     * Handle OAuth callback from Keycloak
     */
    public function callback()
    {
        try {
            // Get OIDC client and complete the authentication process
            $oidc = $this->getOidcClient();
            $oidc->authenticate();

            // If authentication is successful, get user info
            $userInfo = $oidc->requestUserInfo();
            $accessToken = $oidc->getAccessToken();
            $idToken = $oidc->getIdToken();

            // Store user info in session
            $session = session();
            $session->set([
                'logged_in' => true,
                'user_info' => $userInfo,
                'access_token' => $accessToken,
                'id_token' => $idToken
            ]);

            // Redirect to dashboard
            return redirect()->to('/dashboard');

        } catch (\Exception $e) {
            log_message('error', 'Keycloak authentication error: ' . $e->getMessage());
            return redirect()->to('/')->with('error', 'Authentication failed: ' . $e->getMessage());
        }
    }

    /**
     * Logout from Keycloak
     */
    public function logout()
    {
        $session = session();
        $idToken = $session->get('id_token');

        // Clear local session
        $session->destroy();

        // Build Keycloak logout URL
        $logoutUrl = $this->config->getLogoutEndpoint() . '?' . http_build_query([
            'post_logout_redirect_uri' => base_url(),
            'id_token_hint' => $idToken
        ]);

        // Redirect to Keycloak logout
        return redirect()->to($logoutUrl);
    }

    /**
     * Check if user is authenticated
     */
    public function checkAuth()
    {
        $session = session();

        if (!$session->get('logged_in')) {
            return redirect()->to('/auth/login');
        }

        return true;
    }
}
