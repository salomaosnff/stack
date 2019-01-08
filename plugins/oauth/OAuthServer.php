<?php
namespace Stack\Plugins\OAuth;

use Stack\Lib\HttpError;
use Stack\Lib\HttpRequest;
use Stack\Lib\HttpResponse;

class OAuthTokenServer {
    private $controller = '';

    public function __construct($controller) {
        $this->controller = $controller;
    }

    public function callMethod($name, ...$args) {
        if (!\function_exists("$this->controller::$name") && in_array($name, ['saveAccessToken'])) {
            return true;
        }
        return $this->controller::$name(...$args);
    }

    private function token(OAuthRequest $request) {
        if (empty($request->authorization)) {
            return new HttpError(HttpError::BAD_REQUEST, ['code' => 'full_authentication_required']);
        }

        if (empty($request->grant_type)) {
            return new HttpError(HttpError::BAD_REQUEST, ['code' => 'missing grant_type']);
        }

        if ($request->grant_type === 'password') {
            return $this->auth_password($request);
        }

        if ($request->grant_type === 'refresh_token') {
            return $this->auth_refresh_token($request);
        }
    }

    private function auth_password(OAuthRequest $request) {
        $client = $this->callMethod('getClient', $request->client->id, $request->client->secret);
        if (empty($client)) {
            return new HttpError(HttpError::BAD_REQUEST, ['code' => 'invalid_client']);
        }

        $user = $this->callMethod('getUser', $request->username, $request->password);
        if (empty($user)) {
            return new HttpError(HttpError::BAD_REQUEST, ['code' => 'invalid_credentials']);
        }

        $accessToken = $this->callMethod('generateAccessToken', $client, $user);
        if (empty($accessToken)) {
            return new HttpError(HttpError::INTERNAL_SERVER_ERROR, ['code' => 'invalid_access_token']);
        }

        $refreshToken = $this->callMethod('generateRefreshToken', $client, $user, $accessToken);
        if (empty($refreshToken)) {
            return new HttpError(HttpError::INTERNAL_SERVER_ERROR, ['code' => 'invalid_refresh_token']);
        }

        $save = $this->callMethod('saveAccessToken', $accessToken, $refreshToken, $client, $user);

        if (!$save) {
            return new HttpError(HttpError::INTERNAL_SERVER_ERROR, ['code' => 'failed_to_save_access_token']);
        }

        return (object) [
            'client' => $client,
            'user' => $user,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
        ];
    }

    public function auth_refresh_token(OAuthRequest $request) {
        $payload = $this->callMethod('getRefreshToken', $request->refresh_token);
        if (empty($payload)) {
            return new HttpError(HttpError::BAD_REQUEST, ['code' => 'invalid_refresh_token']);
        }

        $payload = (object) $payload;

        $client = $this->callMethod('getClient', $request->client->id, $request->client->secret);
        if (empty($client)) {
            return new HttpError(HttpError::BAD_REQUEST, ['code' => 'invalid_client']);
        }

        $client = (object) $client;

        $user = $this->callMethod('getUser', $payload->user);
        if (empty($user)) {
            return new HttpError(HttpError::BAD_REQUEST, ['code' => 'invalid_user']);
        }

        $user = (object) $user;

        $accessToken = $this->callMethod('generateAccessToken', $client, $user);
        if (empty($accessToken)) {
            return new HttpError(HttpError::INTERNAL_SERVER_ERROR, ['code' => 'invalid_access_token']);
        }

        $refreshToken = $this->callMethod('generateRefreshToken', $client, $user, $accessToken);
        if (empty($refreshToken)) {
            return new HttpError(HttpError::INTERNAL_SERVER_ERROR, ['code' => 'invalid_refresh_token']);
        }

        $save = $this->callMethod('saveAccessToken', $accessToken, $refreshToken, $client, $user);

        if (!$save) {
            return new HttpError(HttpError::INTERNAL_SERVER_ERROR, ['code' => 'failed_to_save_access_token']);
        }

        return (object) [
            'client' => $client,
            'user' => $user,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
        ];
    }

    public function server(HttpRequest &$request, HttpResponse $response) {
        $result = static::token(new OAuthRequest($request));

        if ($result instanceof \Exception) {
            return $result;
        }

        return $response->json([
            'access_token' => $result->access_token,
            'refresh_token' => $result->refresh_token,
        ]);
    }

    public function session(HttpRequest &$request, HttpResponse &$response) {
        $oauth_req = $request->oauth_request;

        if (empty($oauth_req->authorization)) {
            return new HttpError(HttpError::FORBIDDEN, ['code' => 'missing_authorization']);
        }

        $payload = $this->callMethod('getAccessToken', $oauth_req->authorization);
        if (!$payload) {
            return new HttpError(HttpError::BAD_REQUEST, ['code' => 'invalid_access_token']);
        }

        $client = $this->callMethod('getClient', $payload->client);
        if (!$client) {
            return new HttpError(HttpError::BAD_REQUEST, ['code' => 'invalid_client']);
        }

        $user = $this->callMethod('getUser', $payload->aud);
        if (!$user) {
            return new HttpError(HttpError::BAD_REQUEST, ['code' => 'invalid_user']);
        }

        $request->auth = (object) [
            'user' => $user,
            'client' => $client,
            'access_token' => $oauth_req->authorization,
        ];
    }
}
