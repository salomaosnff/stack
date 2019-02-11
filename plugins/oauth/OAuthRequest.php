<?php
namespace Stack\Plugins\OAuth;

use Stack\Lib\HttpError;
use Stack\Lib\HttpRequest;

/**
 * Class OAuthRequest
 * @package Stack\Plugins\OAuth
 */
class OAuthRequest {

    public $authorization = null;
    public $grant_type = null;
    public $client = null;
    public $username = null;
    public $password = null;
    public $refresh_token = null;
    public $token_payload = null;
    public $form = [];

    /**
     * @param HttpRequest $req
     * @throws HttpError
     */
    public function __construct(HttpRequest $req) {
        $authorization = $req->headers['authorization'] ?? '';

        $this->authorization = preg_replace('@^\s*B(earer|asic)|\s*@', '', $authorization);
        $this->form = (object) $req->body;
        $this->grant_type = $this->form->grant_type ?? null;

        if (in_array($this->grant_type, ['password', 'refresh_token'])) {
            $this->client = $this->getClientCredentials();
        }

        if ($this->grant_type === 'password') {
            $this->username = \filter_var($this->form->username, \FILTER_SANITIZE_STRING);
            $this->password = \filter_var($this->form->password, \FILTER_SANITIZE_STRING);

            if (empty($this->username) || empty($this->password)) {
                throw new HttpError(HttpError::BAD_REQUEST, 'missing_credentials');
            }
        }

        if ($this->grant_type === 'refresh_token') {
            $this->refresh_token = $this->form->refresh_token;

            if (empty($this->refresh_token)) {
                throw new HttpError(HttpError::BAD_REQUEST, 'missing_refresh_token');
            }
        }
    }

    /**
     * Get client credentials from authorization
     *
     * @return object
     */
    private function getClientCredentials() {
        $credentials = base64_decode($this->authorization);
        @list($id, $secret) = explode(":", $credentials);
        return (object) [
            'id' => $id,
            'secret' => $secret,
        ];
    }

    /**
     * @param string ...$scope
     * @return bool
     */
    public function hasScope(string ...$scope) {
        return count(array_intersect($scope, $this->token_payload->scopes)) == count($scope);
    }
}
