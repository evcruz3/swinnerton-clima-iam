<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Jumbojett\OpenIDConnectClient;

/**
 * Keycloak_auth Library
 *
 * Wrapper for Keycloak OIDC authentication
 */
class Keycloak_auth
{
    protected $CI;
    protected $oidc;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->config('keycloak');

        $this->initialize_oidc();
    }

    /**
     * Initialize the OIDC client
     */
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

        // Add scopes (addScope expects an array)
        $this->oidc->addScope($scopes);
    }

    /**
     * Initiate login process
     */
    public function login()
    {
        try {
            $this->oidc->authenticate();
        } catch (Exception $e) {
            log_message('error', 'Keycloak authentication error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle callback and complete authentication
     *
     * @return array User info
     */
    public function handle_callback()
    {
        try {
            // The authenticate() call will handle the callback
            $this->oidc->authenticate();

            // Get tokens
            $access_token = $this->oidc->getAccessToken();
            $id_token = $this->oidc->getIdToken();

            // Get user info
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

    /**
     * Logout user
     */
    public function logout()
    {
        $server_url = $this->CI->config->item('keycloak_server_url');
        $realm = $this->CI->config->item('keycloak_realm');

        // Destroy session
        $this->CI->session->sess_destroy();

        // Return Keycloak logout URL
        return $server_url . '/realms/' . $realm . '/protocol/openid-connect/logout';
    }

    /**
     * Check if user is authenticated
     *
     * @return bool
     */
    public function is_authenticated()
    {
        return $this->CI->session->userdata('logged_in') === TRUE;
    }

    /**
     * Get user info from session
     *
     * @return mixed
     */
    public function get_user_info()
    {
        return $this->CI->session->userdata('user_info');
    }

    /**
     * Get access token
     *
     * @return mixed
     */
    public function get_access_token()
    {
        return $this->CI->session->userdata('access_token');
    }

    /**
     * Get ID token
     *
     * @return mixed
     */
    public function get_id_token()
    {
        return $this->CI->session->userdata('id_token');
    }
}
