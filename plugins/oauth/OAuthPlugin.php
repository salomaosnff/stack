<?php
namespace Stack\Plugins\OAuth;

use Stack\Lib\HttpError;
use Stack\Lib\HttpRequest;
use Stack\Lib\HttpResponse;
use Stack\Lib\Router;

class OAuthPlugin {
    public $router;
    public $server;

    public function __construct ($app, $controller, $baseUrl = '/oauth') {
        $this->server = new OAuthTokenServer($controller);
        $this->router =  new Router($baseUrl);
 
        $app->use(function($req, $res) {
            $req->oauth = $this;
            $req->oauth_request = new OAuthRequest($req);
        });

        $this->router->route('/token')
            ->post(function(HttpRequest $request, HttpResponse $response) {
                return $this->server->server($request, $response);
            });

        $app->use($this->router);
    }

    public static function authenticate(...$scopes){
        return function ($req, $res) use ($scopes) {
            return $req->oauth->server->session($req, $res);

//            if (!$req->oauth_request->hasScope(...$scopes)) {
//                return new HttpError(HttpError::FORBIDDEN, 'Insufficient permissions!');
//            }
        };
    }
}
