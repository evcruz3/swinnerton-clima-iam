<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Keycloak Configuration
|--------------------------------------------------------------------------
|
| Configuration for Keycloak OIDC integration
|
*/

/**
 * Keycloak server URL
 */
$config['keycloak_server_url'] = 'https://devsso.swinnertonsolutions.com';

/**
 * Keycloak realm
 */
$config['keycloak_realm'] = 'CLIMA';

/**
 * Client ID
 */
$config['keycloak_client_id'] = 'clima-frontend';

/**
 * Client Secret
 */
$config['keycloak_client_secret'] = 'gQig4u1mLON1CEDxhXQwTI1CaWhCGA8v';

/**
 * Redirect URI (must be registered in Keycloak client)
 */
$config['keycloak_redirect_uri'] = 'http://localhost:8080/auth/callback';

/**
 * Scopes to request
 */
$config['keycloak_scopes'] = ['openid', 'profile', 'email'];
