<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Home
$routes->get('/', 'Home::index');

// Authentication routes
$routes->get('auth/login', 'Auth::login');
$routes->get('auth/callback', 'Auth::callback');
$routes->get('auth/logout', 'Auth::logout');

// Protected routes
$routes->get('dashboard', 'Dashboard::index');
