<?php
namespace Stack\Plugins\OAuth;

interface OAuthControllerInterface {
    static function getClient($client_id, ?string $secret = null): ?object;
    static function getUser($username_or_id, ?string $password = null): ?object;
    static function generateAccessToken(object $client, object $user): ?string;
    static function generateRefreshToken(object $client, object $user, string $accessToken): ?string;
    static function getAccessToken(string $access_token): ?object;
    static function getRefreshToken(string $refresh_token): ?object;
    static function saveToken(object $client, object $user, string $access_token, string $refresh_token): ?object;
}