<?php
namespace Stack\Lib;

use Stack\Lib\HttpRequest;
use Stack\Lib\HttpResponse;
use Stack\Lib\HttpException;

class Route {

  public $url = '/';
  public $regex = '@^/*$@';
  public $params = [];
  public $stack_methods = [];
  public $_controllers = '';

  /**
   * Method registration
   * @param string $method
   * @param array $middlewares
   * @return Route
   */
  private function register_method(string $method, array $middlewares) {
    if (!isset($this->stack_methods[$method])) {
        $this->stack_methods[$method] = new MiddlewareStack($this);
    }

    $middlewares = array_map(function($middleware) {
        return normalize_method($middleware);
    }, $middlewares);

    $this->stack_methods[$method]->use(...$middlewares);
    return $this;
  }

  /**
     * Register GET method
     * @param string|callable ...$middlewares
     * @return Route
     */
    public function get(...$middlewares): Route {
      return $this->register_method('GET', $middlewares);
  }

  /**
   * Register POST method
   * @param string|callable ...$middlewares
   * @return Route
   */
  public function post(...$middlewares): Route {
      return $this->register_method('POST', $middlewares);
  }

  /**
   * Register PUT method
   * @param string|callable ...$middlewares
   * @return Route
   */
  public function put(...$middlewares): Route {
      return $this->register_method('PUT', $middlewares);
  }

  /**
   * Register PATCH method
   * @param string|callable ...$middlewares
   * @return Route
   */
  public function patch(...$middlewares): Route {
      return $this->register_method('PATCH', $middlewares);
  }

  /**
   * Register DELETE method
   * @param string|callable ...$middlewares
   * @return Route
   */
  public function delete(...$middlewares): Route {
      return $this->register_method('DELETE', $middlewares);
  }

  /**
   * Register HEAD method
   * @param string|callable ...$middlewares
   * @return Route
   */
  public function head(...$middlewares): Route {
      return $this->register_method('HEAD', $middlewares);
  }

  /**
   * Register OPTIONS method
   * @param string|callable ...$middlewares
   * @return Route
   */
  public function options(...$middlewares): Route {
      return $this->register_method('OPTIONS', $middlewares);
  }

  /**
   * Resolve os métodos da rota
   * @param HttpRequest $req
   * @param HttpResponse $res
   */
  public function dispatch(HttpRequest $req, HttpResponse $res) {
    if (!test_url($req, false, $this)) return null;
    if($stack = ($this->stack_methods[$req->method] ?? false)) {
      return $stack->next($req, $res);
    }
    return new HttpException(HttpException::METHOD_NOT_ALLOWED);
  }

  public function __get($prop) {
    if($prop === 'controllers') {
      if(!\is_null($this->parent)) {
        return resolve_namespace($this->parent->controllers, $this->_controllers);
      }
      return $this->_controllers;
    }
  }

  /**
   * @param string $url Final URL to be matched
   * @param string $controllers Namespace dos controladores
   * @param array $stack_methods Lista
   */
  public function __construct($url, $controllers = '', $stack_methods = []) {
    $this->url = normalize_url($url);
    $this->_controllers = $controllers;

    $url = url_params($this->url, true);
    $this->regex = $url['regex'];
    $this->params = $url['params'];

    foreach($stack_methods as $method => $middlewares) {
      $this->register_method($method, $middlewares);
    }
  }
}