<?php
namespace Stack\Plugins\OAuth;

use Stack\Lib\HttpError;
use Stack\Lib\HttpRequest;
use Stack\Lib\HttpResponse;

/**
 * Class OAuthTokenServer
 * @package Stack\Plugins\OAuth
 */
class OAuthTokenServer {

    /**
     * Token Server Controller
     *
     * @var string
     */
    private $controller = '';

    /**
     * @param string $controller Token Server Controller
     */
    public function __construct(string $controller) {
        $this->controller = $controller;
    }

    /**
     * Call controller method
     *
     * @param $name
     * @param mixed ...$args
     * @return bool
     */
    public function callMethod($name, ...$args) {
        if (!\function_exists("$this->controller::$name") && in_array($name, ['saveAccessToken'])) {
            return true;
        }
        return $this->controller::$name(...$args);
    }

    /**
     * Generate token from request
     *
     * @param OAuthRequest $request
     * @return object|HttpError
     */
    private function token(OAuthRequest $request) {
        if (empty($request->authorization)) {
            return new HttpError(HttpError::BAD_REQUEST, 'full_authentication_required');
        }

        if (empty($request->grant_type)) {
            return new HttpError(HttpError::BAD_REQUEST, 'missing grant_type');
        }

        if ($request->grant_type === 'password') {
            return $this->auth_password($request);
        }

        if ($request->grant_type === 'refresh_token') {
            return $this->auth_refresh_token($request);
        }
    }

    /**
     * Generate token from user credentials
     *
     * @param OAuthRequest $request
     * @return object|HttpError
     */
    private function auth_password(OAuthRequest $request) {
        $client = $this->callMethod('getClient', $request->client->id, $request->client->secret);
        if (empty($client)) {
            return new HttpError(HttpError::BAD_REQUEST, 'invalid_client');
        }

        $user = $this->callMethod('getUser', $request->username, $request->password);
        if (empty($user)) {
            return new HttpError(HttpError::BAD_REQUEST, 'invalid_credentials');
        }

        $accessToken = $this->callMethod('generateAccessToken', $client, $user);
        if (empty($accessToken)) {
            return new HttpError(HttpError::INTERNAL_SERVER_ERROR, 'invalid_access_token');
        }

        $refreshToken = $this->callMethod('generateRefreshToken', $client, $user, $accessToken);
        if (empty($refreshToken)) {
            return new HttpError(HttpError::INTERNAL_SERVER_ERROR, 'invalid_refresh_token');
        }

        $save = $this->callMethod('saveAccessToken', $accessToken, $refreshToken, $client, $user);

        if (!$save) {
            return new HttpError(HttpError::INTERNAL_SERVER_ERROR, 'failed_to_save_access_token');
        }

        return (object) [
            'client' => $client,
            'user' => $user,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
        ];
    }

    /**
     * Generate token from gave refresh token in request
     *
     * @param OAuthRequest $request
     * @return object|HttpError
     */
    public function auth_refresh_token(OAuthRequest $request) {
        $payload = $this->callMethod('getRefreshToken', $request->refresh_token);
        if (empty($payload)) {
            return new HttpError(HttpError::BAD_REQUEST, 'invalid_refresh_token');
        }

        $payload = (object) $payload;

        $client = $this->callMethod('getClient', $request->client->id, $request->client->secret);
        if (empty($client)) {
            return new HttpError(HttpError::BAD_REQUEST, 'invalid_client');
        }

        $client = (object) $client;

        $user = $this->callMethod('getUser', $payload->user);
        if (empty($user)) {
            return new HttpError(HttpError::BAD_REQUEST, 'invalid_user');
        }

        $user = (object) $user;

        $accessToken = $this->callMethod('generateAccessToken', $client, $user);
        if (empty($accessToken)) {
            return new HttpError(HttpError::INTERNAL_SERVER_ERROR, 'invalid_access_token');
        }

        $refreshToken = $this->callMethod('generateRefreshToken', $client, $user, $accessToken);
        if (empty($refreshToken)) {
            return new HttpError(HttpError::INTERNAL_SERVER_ERROR, 'invalid_refresh_token');
        }

        $save = $this->callMethod('saveAccessToken', $accessToken, $refreshToken, $client, $user);

        if (!$save) {
            return new HttpError(HttpError::INTERNAL_SERVER_ERROR, 'failed_to_save_access_token');
        }

        return (object) [
            'client' => $client,
            'user' => $user,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
        ];
    }

    /**
     * Token generation server
     *
     * @param HttpRequest $request
     * @param HttpResponse $response
     * @return object|HttpError|HttpResponse
     * @throws HttpError
     */
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


    public function session(HttpRequest &$req, HttpResponse &$res) {
        if(! isset($req->oauth_request)) {
            return new HttpError(HttpError::INTERNAL_SERVER_ERROR, 'missing_oauth_request');
        }

        $oauth_req = $req->oauth_request;

        if (empty($oauth_req->authorization)) {
            return new HttpError(HttpError::FORBIDDEN, 'missing_authorization');
        }

        $payload = $this->callMethod('getAccessToken', $oauth_req->authorization);
        if (!$payload) {
            return new HttpError(HttpError::BAD_REQUEST, 'invalid_access_token');
        }

        $client = $this->callMethod('getClient', $payload->client);
        if (!$client) {
            return new HttpError(HttpError::BAD_REQUEST, 'invalid_client');
        }

        $user = $this->callMethod('getUser', $payload->aud);
        if (!$user) {
            return new HttpError(HttpError::BAD_REQUEST, 'invalid_user');
        }

        $req->auth = (object) [
            'user' => $user,
            'client' => $client,
            'access_token' => $oauth_req->authorization,
        ];
    }
}
