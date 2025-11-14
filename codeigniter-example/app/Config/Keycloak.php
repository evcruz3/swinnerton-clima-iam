<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Keycloak extends BaseConfig
{
    /**
     * Keycloak server URL
     */
    public string $serverUrl = 'https://devsso.swinnertonsolutions.com';

    /**
     * Keycloak realm
     */
    public string $realm = 'CLIMA';

    /**
     * Client ID
     */
    public string $clientId = 'clima-frontend';

    /**
     * Client Secret
     */
    public string $clientSecret = 'gQig4u1mLON1CEDxhXQwTI1CaWhCGA8v';

    /**
     * Redirect URI (must be registered in Keycloak client)
     */
    public string $redirectUri = 'http://localhost:8080/auth/callback';

    /**
     * Scopes to request
     */
    public array $scopes = ['openid', 'profile', 'email'];

    /**
     * Get the full issuer URL
     */
    public function getIssuerUrl(): string
    {
        return $this->serverUrl . '/realms/' . $this->realm;
    }

    /**
     * Get the authorization endpoint
     */
    public function getAuthorizationEndpoint(): string
    {
        return $this->getIssuerUrl() . '/protocol/openid-connect/auth';
    }

    /**
     * Get the token endpoint
     */
    public function getTokenEndpoint(): string
    {
        return $this->getIssuerUrl() . '/protocol/openid-connect/token';
    }

    /**
     * Get the userinfo endpoint
     */
    public function getUserInfoEndpoint(): string
    {
        return $this->getIssuerUrl() . '/protocol/openid-connect/userinfo';
    }

    /**
     * Get the logout endpoint
     */
    public function getLogoutEndpoint(): string
    {
        return $this->getIssuerUrl() . '/protocol/openid-connect/logout';
    }
}
