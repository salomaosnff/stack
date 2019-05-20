<?php
namespace Stack\Lib;

use Stack\Lib\HttpRequest;
use Stack\Lib\HttpResponse;

class Router extends RouterMethods {
  
  /**
   * @var MiddlewareStack
   */
  public $stack;

  /**
   * @param string $baseURL Base url for routing
   * @param string $controllers Namespace for controllers
   * @param MiddlewareStack $stack A MiddlewareStack instance to use
   */
  public function __construct(
    $baseURL = '/', 
    $controllers = '',
    ?MiddlewareStack $stack = null
  ) {
    parent::__construct($baseURL, $controllers);
    $this->stack = $stack ?? new MiddlewareStack($this);
  }

  /**
   * Use a middlewire inside the router
   * @param callable|Router ...$middlewares Middleware list
   */
  public function use(...$middlewares) {
    foreach($middlewares as $mid) {
      if($mid instanceof self || $mid instanceof Route) {
        $mid->parent = $this;
      }
    }
    $this->stack->use(...$middlewares);
    return $this;
  }

  /**
   * Register new route url
   *
   * @param string $url Final path for routing method
   * @param array $stack_methods Assoc. Array with methods and middlewares
   * @example $route('/:id', ['GET' => [function() { ... }, function() { ... }]])
   * @return Route
   */
  public function route($url, string $controllers = '', array $stack_methods = []): Route {
    $route = new Route($url, $controllers, $stack_methods);
    $this->use($route);
    return $route;
  }

  /**
   * Dispath router middlewares
   */
  public function dispatch(HttpRequest $req, HttpResponse $res) {
    if(!test_url($req, true, $this)) return null;
    return $this->stack->next($req, $res);
  }
}