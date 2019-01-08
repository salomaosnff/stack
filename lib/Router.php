<?php
namespace Stack\Lib;

class Router extends Routeable {
    protected $routes = [];
    protected $sub_routers = [];

    public function __construct(string $url = '/') {
        parent::__construct($url, 'router');
    }

    public function route($url): Route {
        $route = new Route($url);
        $this->routes[] = $route;
        return $route;
    }

    public function use (...$middlewares) {
        foreach ($middlewares as $middleware) {
            if ($middleware instanceof Router) {
                $this->sub_routers[] = $middleware;
            }parent::use ($middleware);
        }
    }

    public function init(HttpRequest &$request, HttpResponse &$response, $err = null) {
        if (!$this->test($request, true)) {
            return null;
        }

        $res = parent::init($request, $response, 'router');

        if (!MiddlewareStack::__check_value($res)) {
            return $res;
        }

        foreach ($this->sub_routers as $router) {
            $res = $router->init($request, $response);
            if (!is_null($res) && $res) {
                return $res;
            }

        }

        foreach ($this->routes as $route) {
            $res = $route->init($request, $response);
            if (!is_null($res) && $res) {
                return $res;
            }

        }

        return null;
    }
}