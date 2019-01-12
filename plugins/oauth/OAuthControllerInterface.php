<?php
namespace Stack\Plugins\OAuth;

/**
 * OAuth Controller Interface class
 * @package Stack\Plugins\OAuth
 */
interface OAuthControllerInterface {

    /**
     * Get client credentials
     *
     * @param $client_id
     * @param string|null $secret
     * @return object|null
     */
    static function getClient($client_id, ?string $secret = null): ?object;

    /**
     * Get user credentials
     *
     * @param $username_or_id
     * @param string|null $password
     * @return object|null
     */
    static function getUser($username_or_id, ?string $password = null): ?object;

    /**
     * Generate and return a access token
     *
     * @param object $client
     * @param object $user
     * @return string|null
     */
    static function generateAccessToken(object $client, object $user): ?string;

    /**
     * Generate and return a refresh token
     *
     * @param object $client
     * @param object $user
     * @param string $accessToken
     * @return string|null
     */
    static function generateRefreshToken(object $client, object $user, string $accessToken): ?string;

    /**
     * Get the access token payload
     *
     * @param string $access_token
     * @return object|null
     */
    static function getAccessToken(string $access_token): ?object;

    /**
     * Get refresh token payload
     *
     * @param string $refresh_token
     * @return object|null
     */
    static function getRefreshToken(string $refresh_token): ?object;

    /**
     * Function to save the access token somewhere in the back-end
     *
     * @param object $client
     * @param object $user
     * @param string $access_token
     * @param string $refresh_token
     * @return object|null
     */
    static function saveToken(object $client, object $user, string $access_token, string $refresh_token): ?object;
}