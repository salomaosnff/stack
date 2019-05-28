<?php
namespace Stack\Plugins\OAuth;

/**
 * OAuth Controller implementation class
 * @package Stack\Plugins\OAuth
 */
abstract class OAuthControllerBase
{

    /**
     * Get client credentials
     *
     * @param $client_id
     * @param string|null $secret
     * @return object|null
     */
    abstract public function getClient($client_id, ?string $secret = null): ?object;

    /**
     * Get user credentials
     *
     * @param $username_or_id
     * @param string|null $password
     * @return object|null
     */
    abstract public function getUser($username_or_id, ?string $password = null): ?object;

    /**
     * Generate and return a access token
     *
     * @param object $client
     * @param object $user
     * @return string|null
     */
    abstract public function generateAccessToken(object $client, object $user): ?string;

    /**
     * Generate and return a refresh token
     *
     * @param object $client
     * @param object $user
     * @param string $accessToken
     * @return string|null
     */
    abstract public function generateRefreshToken(object $client, object $user, string $accessToken): ?string;

    /**
     * Get the access token payload
     *
     * @param string $access_token
     * @return object|null
     */
    abstract public function getAccessToken(string $access_token): ?object;

    /**
     * Get refresh token payload
     *
     * @param string $refresh_token
     * @return object|null
     */
    abstract public function getRefreshToken(string $refresh_token): ?object;

    /**
     * Function to save the access token somewhere in the back-end
     *
     * @param object $client
     * @param object $user
     * @param string $access_token
     * @param string $refresh_token
     * @return object|null
     */
    abstract public function saveToken(object $client, object $user, string $access_token, string $refresh_token): ?object;

    /**
     * Revoke a token to database
     *
     * @param array|object $access_token
     * @param array|object $refresh_token
     * @return bool|null
     */
    public function revokeToken($access_token, $refresh_token): ?bool
    {return true;}

    /**
     * Validate a access token
     *
     * @param $access_token
     * @param array $options
     * @return bool
     */
    public function validateAccessToken($access_token, array $options = []): bool
    {return true;}

    /**
     * Validate a refresh token
     *
     * @param $refresh_token
     * @param array $options
     * @return bool
     */
    public function validateRefreshToken($refresh_token, array $options = []): bool
    {return true;}
}
