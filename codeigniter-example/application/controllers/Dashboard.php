<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends MY_Controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        // Require authentication for this controller
        $this->require_auth();
    }

    /**
     * Dashboard index page
     */
    public function index()
    {
        // Get user info from session
        $data['user'] = $this->get_user_info();

        // Load dashboard view
        $this->load->view('dashboard', $data);
    }
}
