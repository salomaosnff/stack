<?php
namespace Stack\Plugins\OAuth;

use Stack\Lib\HttpException;
use Stack\Lib\HttpRequest;
use Stack\Lib\HttpResponse;
use Stack\Lib\Router;

/**
 * OAuth JWT Plugin
 *
 * @package Stack\Plugins\OAuth
 */
class OAuthPlugin
{

    public $router;
    public $server;

    /**
     * @param Router $app Main app router
     * @param string $controller OAuth Controller class name
     * @param array $options Options for OAuth plugin
     */
    public function __construct(
        Router $app,
        string $controller,
        array $options = []
    ) {
        $options = array_replace([
            'base_url'   => '/oauth',
            'token_url'  => '/token',
            'revoke_url' => '/token/revoke',
        ], $options);

        $this->server = new OAuthTokenServer($controller);
        $this->router = new Router($options['base_url']);

        /**
         * OAuth Request injection
         */
        $app->use(function (HttpRequest $req) {
            $req->oauth         = $this;
            $req->oauth_request = new OAuthRequest($req);
        });

        /**
         * Send no-cache headers in auth route
         */
        $this->router->use(function (HttpResponse $res) {
            $res->headers([
                'Cache-Control' => 'no-cache',
                'Pragma'        => 'no-cache',
            ]);
        });

        /**
         * Token route
         */
        $this->router->route($options['token_url'])
            ->post(function (HttpRequest $req, HttpResponse $res) {
                return $this->server->server($req, $res);
            });

        /**
         * Token revoke route
         */
        $this->router->route($options['revoke_url'])
            ->delete(function (HttpRequest $req, HttpResponse $res) {
                return $this->server->revoke($req, $res);
            });

        $app->use($this->router);
    }

    /**
     * Force route to be authenticated
     *
     * @param mixed ...$scopes
     * @return \Closure
     */
    public static function auth(...$scopes)
    {
        return function (HttpRequest $req, HttpResponse $res) use ($scopes) {
            return self::authRequest($req, $res, $scopes);
        };
    }

    /**
     * Force request to be authenticated
     *
     * @param HttpRequest $req
     * @param HttpResponse $res
     * @param array $scopes
     */
    public static function authRequest(
        HttpRequest $req,
        HttpResponse $res,
        array $scopes = []
    ) {
        if (!isset($req->oauth)) {
            throw new HttpException(HttpException::INTERNAL_SERVER_ERROR, 'oauth_plugin_not_started');
        }
        return $req->oauth->server->session($req, $res);

        // TODO: Scopos e permissões
        // if (!$req->oauth_request->hasScope(...$scopes)) {
        //     return new HttpException(HttpException::FORBIDDEN, 'Insufficient permissions!');
        // }
    }
}
