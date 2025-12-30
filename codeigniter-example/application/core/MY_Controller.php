<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MY_Controller
 *
 * Base controller with common functionality
 */
class MY_Controller extends CI_Controller
{
    /**
     * List of controllers that don't require authentication
     */
    protected $public_controllers = array('auth', 'home');

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Check if user is authenticated
     *
     * @return bool
     */
    protected function is_authenticated()
    {
        return $this->session->userdata('logged_in') === TRUE;
    }

    /**
     * Get current user info
     *
     * @return mixed
     */
    protected function get_user_info()
    {
        return $this->session->userdata('user_info');
    }

    /**
     * Require authentication - redirect to login if not authenticated
     */
    protected function require_auth()
    {
        if (!$this->is_authenticated()) {
            $this->session->set_flashdata('error', 'Please login to access this page');
            redirect('auth/login');
        }
    }

    /**
     * Check if current controller requires authentication
     *
     * @return bool
     */
    public function needs_authentication()
    {
        $controller = $this->router->fetch_class();
        return !in_array(strtolower($controller), $this->public_controllers);
    }
}
