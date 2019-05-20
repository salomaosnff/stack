<?php
namespace Stack\Lib;

abstract class RouterMethods {

  /**
   * @var string
   */
  public $url = '/';

  /**
   * @var string
   */
  protected $_controllers = '';

  /**
   * @var Router|Route|null
   */
  public $parent = null;

  /**
   * @var string
   */
  public $regex = '@^/*$@';

  /**
   * @var array
   */
  public $params = [];

  /**
   * Custom parser for middleware parameters
   * 
   * @var callable
   */
  private $_middlewareParamParser;

  /**
   * Middleware dispatcher
   */
  abstract public function dispatch(HttpRequest $req, HttpResponse $res);

  /**
   * @param string $url URL to be matched
   * @param string $controllers Controllers namespace
   */
  public function __construct($url, $controllers = '') {
    $this->url = normalize_url($url);
    $this->_controllers = $controllers;

    $url = url_params($this->url, $this instanceof Route);
    $this->regex = $url['regex'];
    $this->params = $url['params'];
  }

  /**
   * Custom props getter
   */
  public function __get($prop) {
    if($prop === 'controllers') {
      if(!\is_null($this->parent)) {
        return resolve_namespace($this->parent->controllers, $this->_controllers);
      }
      return $this->_controllers;
    }
  }

  /**
   * Set a custom middleware param parser
   * 
   * @param callable $parser Function to parse params
   */
  public function setMiddlewareParamParser(callable $parser) {
    $this->_middlewareParamParser = $parser;
    return $this;
  }

  /**
   * Call the middleware param parser
   * 
   * @param $args Array with arguments for middleware parser
   */
  public function middlewareParamParser($args) {
    $args = is_array($args) ? $args : func_get_args();
    if(isset($this->_middlewareParamParser) && \is_callable($this->_middlewareParamParser)) {
      return \call_user_func_array($this->_middlewareParamParser, $args);
    }
    else if(! is_null($this->parent)) {
      return $this->parent->middlewareParamParser($args);
    }
    return null;
  }
}