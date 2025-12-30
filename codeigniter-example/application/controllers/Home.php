<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends MY_Controller
{
    /**
     * Home page / Welcome page
     */
    public function index()
    {
        $this->load->view('welcome');
    }
}
