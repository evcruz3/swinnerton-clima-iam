<?php

namespace App\Controllers;

class Dashboard extends BaseController
{
    public function index()
    {
        $session = session();

        // Check if user is logged in
        if (!$session->get('logged_in')) {
            return redirect()->to('/auth/login');
        }

        // Get user info from session
        $data['user'] = $session->get('user_info');

        return view('dashboard', $data);
    }
}
