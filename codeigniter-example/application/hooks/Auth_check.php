<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Auth_check Hook
 *
 * Checks authentication for protected routes
 */
class Auth_check
{
    /**
     * Check if user is authenticated for protected routes
     */
    public function check_authentication()
    {
        $CI =& get_instance();

        // Get the current controller
        $controller = $CI->router->fetch_class();

        // List of public controllers that don't require authentication
        $public_controllers = array('auth', 'home');

        // If controller is not public, check authentication
        if (!in_array(strtolower($controller), $public_controllers)) {
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
}
