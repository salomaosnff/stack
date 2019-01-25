<?php
namespace Stack\Plugins\OAuth;

use Stack\Lib\HttpError;
use Stack\Lib\HttpRequest;
use Stack\Lib\HttpResponse;
use Stack\Lib\Routeable;
use Stack\Lib\Router;

/**
 * OAuth JWT Plugin
 *
 * @package Stack\Plugins\OAuth
 */
class OAuthPlugin {

    public $router;
    public $server;

    /**
     * @param Routeable $app
     * @param string $controller
     * @param string $baseUrl
     */
    public function __construct (Routeable $app, string $controller, string $baseUrl = '/oauth') {
        $this->server = new OAuthTokenServer($controller);
        $this->router =  new Router($baseUrl);

        $app->use(function(HttpRequest $req) {
            $req->oauth = $this;
            $req->oauth_request = new OAuthRequest($req);
        });

        $this->router->route('/token')
            ->post(function(HttpRequest $request, HttpResponse $response) {
                return $this->server->server($request, $response);
            });

        $app->use($this->router);
    }

    /**
     * Force route to be authenticated
     *
     * @param mixed ...$scopes
     * @return \Closure
     */
    public static function authenticate(...$scopes){
        return function (HttpRequest $req, HttpResponse $res) use ($scopes) {
            return self::authRequest($req, $res, $scopes);
        };
    }

    /**
     * Check authentication in a request
     *
     * @param HttpRequest $req
     * @param HttpResponse $res
     * @param array $scopes
     * @return HttpError
     */
    public static function authRequest(HttpRequest $req, HttpResponse $res, array $scopes = []) {
        if(! isset($req->oauth)) {
            return new HttpError(HttpError::INTERNAL_SERVER_ERROR, 'oauth_plugin_not_started');
        }
        return $req->oauth->server->session($req, $res);

//            if (!$req->oauth_request->hasScope(...$scopes)) {
//                return new HttpError(HttpError::FORBIDDEN, 'Insufficient permissions!');
//            }
    }
}
