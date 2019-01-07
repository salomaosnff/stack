<?php
namespace Stack\Plugins\OAuth;

use Stack\Lib\HttpError;
use Stack\Lib\HttpRequest;

class OAuthRequest {
    public $authorization = null;
    public $grant_type = null;
    public $username = null;
    public $password = null;
    public $refresh_token = null;
    public $client = null;
    public $token_payload = null;

    public function __construct(HttpRequest $request) {
        $this->authorization = preg_replace('@^\s*B(earer|asic)|\s*@', '', $request->headers['authorization'] ?? '');
        $this->form = (object) $request->body;
        $this->grant_type = isset($this->form->grant_type) ? $this->form->grant_type : null;

        $is_bearer = preg_match('@^Bearer @', $request->headers['authorization']);

        if ($is_bearer) {
            $this->token_payload = JWT::decode($this->authorization);
            $this->token_payload->scopes = $this->token_payload->scopes ?? [];
            return $this;
        }

        if (in_array($this->grant_type, ['password', 'refresh_token'])) {
            $this->client = $this->getClientCredentials();
        }

        if ($this->grant_type === 'password') {
            $this->username = \filter_var($this->form->username, \FILTER_SANITIZE_STRING);
            $this->password = \filter_var($this->form->password, \FILTER_SANITIZE_STRING);

            if (empty($this->username) || empty($this->password)) {
                throw new HttpError(HttpError::BAD_REQUEST, ['code' => 'missing_credentials']);
            }
        }

        if ($this->grant_type === 'refresh_token') {
            $this->refresh_token = $this->form->refresh_token;

            if (empty($this->refresh_token)) {
                throw new HttpError(HttpError::BAD_REQUEST, ['code' => 'missing_refresh_token']);
            }
        }
    }

    private function getClientCredentials() {
        $credentials = base64_decode($this->authorization);
        list($id, $secret) = explode(":", $credentials);

        return (object) [
            'id' => $id,
            'secret' => $secret,
        ];
    }

    public function hasScope(string ...$scope) {
        return count(array_intersect($scope, $this->token_payload->scopes)) == count($scope);
    }
}
