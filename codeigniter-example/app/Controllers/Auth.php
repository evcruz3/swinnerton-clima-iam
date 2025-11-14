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

        // Initialize OpenID Connect Client
        $this->oidc = new OpenIDConnectClient(
            $this->config->serverUrl . '/realms/' . $this->config->realm,
            $this->config->clientId,
            $this->config->clientSecret
        );

        // Set the redirect URI
        $this->oidc->setRedirectURL($this->config->redirectUri);

        // Add scopes
        foreach ($this->config->scopes as $scope) {
            $this->oidc->addScope($scope);
        }
    }

    /**
     * Initiate login process
     */
    public function login()
    {
        try {
            $this->oidc->authenticate();

            // If authentication is successful, get user info
            $userInfo = $this->oidc->requestUserInfo();
            $accessToken = $this->oidc->getAccessToken();
            $idToken = $this->oidc->getIdToken();

            // Store user info in session
            $session = session();
            $session->set([
                'logged_in' => true,
                'user_info' => $userInfo,
                'access_token' => $accessToken,
                'id_token' => $idToken
            ]);

            // Redirect to dashboard or home
            return redirect()->to('/dashboard');

        } catch (\Exception $e) {
            log_message('error', 'Keycloak authentication error: ' . $e->getMessage());
            return redirect()->to('/')->with('error', 'Authentication failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle OAuth callback
     */
    public function callback()
    {
        // This is handled by the authenticate() method in login()
        // But we keep this route for clarity
        return redirect()->to('/auth/login');
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
