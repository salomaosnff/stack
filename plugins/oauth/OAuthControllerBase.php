<?php
namespace Stack\Plugins\OAuth;

use Stack\Lib\HttpRequest;
use Stack\Lib\HttpResponse;

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
     * @param array $data Access token, refresh token, user and client in an assoc. array
     * @param HttpRequest $req Http request
     * @param HttpResponse $res Http response
     * @return object|array|null
     */
    abstract public function saveToken(array $data, HttpRequest $req, HttpResponse $res);

    /**
     * Revoke a token to database
     *
     * @param array|object $access_token
     * @param array|object $refresh_token
     * @param HttpRequest $req
     * @param HttpResponse $res
     * @return bool|null
     */
    public function revokeToken($access_token, $refresh_token, HttpRequest $req, HttpResponse $res): ?bool
    {
        return true;
    }

    /**
     * Validate session
     * @param HttpRequest $req
     * @param HttpResponse $res
     */
    public function validateSession(HttpRequest $req, HttpResponse $res)
    {
        return true;
    }
}
