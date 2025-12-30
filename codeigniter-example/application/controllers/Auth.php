<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends MY_Controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        // Load Keycloak authentication library
        $this->load->library('keycloak_auth');

        // Start session before OIDC client
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Initiate login process
     */
    public function login()
    {
        // Check if already logged in
        if ($this->session->userdata('logged_in')) {
            redirect('dashboard');
            return;
        }

        try {
            // Redirect to Keycloak for authentication
            $this->keycloak_auth->login();

            // Note: login() will redirect to Keycloak, so code below won't execute
            // The callback will be handled by the callback() method
        } catch (Exception $e) {
            log_message('error', 'Keycloak login error: ' . $e->getMessage());
            $this->session->set_flashdata('error', 'Login failed: ' . $e->getMessage());
            redirect('/');
        }
    }

    /**
     * Handle OAuth callback from Keycloak
     */
    public function callback()
    {
        try {
            // Complete authentication and get user info
            $user_info = $this->keycloak_auth->handle_callback();

            // Redirect to dashboard
            redirect('dashboard');

        } catch (Exception $e) {
            log_message('error', 'Keycloak callback error: ' . $e->getMessage());
            $this->session->set_flashdata('error', 'Authentication failed: ' . $e->getMessage());
            redirect('/');
        }
    }

    /**
     * Logout from Keycloak
     */
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
}
