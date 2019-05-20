<?php
namespace Stack\Lib;

use Stack\Lib\HttpRequest;
use Stack\Lib\HttpResponse;

/**
 * Class MiddlewareStack
 * @package Stack\Lib
 */
class MiddlewareStack {

    /**
     * @var array
     */
    public $stack = [];

    /**
     * Controllers namespace
     *
     * @var string
     */
    public $_controllers = '';

    /**
     * Parent router
     * 
     * @var null|Router|Route
     */
    public $parent;

    /**
     * Registra middlewares na stack
     *
     * @param callable|Router|Route|array $middlewares Lista de middlewares
     * @return MiddlewareStack
     */
    public function use(...$middlewares) {
        $middlewares = array_filter($middlewares, function($middleware) {
            return is_callable($middleware, true) || ($middleware instanceof self) || 
                ($middleware instanceof Router) || ($middleware instanceof Route)
            ;
        });
        $this->stack = array_merge($this->stack, $middlewares);
        return $this;
    }

    /**
     * @param $req
     * @param $res
     * @param null $err
     * @return HttpResponse|null
     * @throws \ReflectionException
     */
    public function next(HttpRequest &$req, HttpResponse &$res, $out = null) {
        if (count($this->stack) <= 0) {
            return $out;
        };

        // Return the output if have one
        if(!is_null($out) && !($out instanceof \Exception)) {
            return $out;
        }
        
        $middleware = array_shift($this->stack);
        $middleware = normalize_method($middleware, $this->controllers);

        $reflex = null;
        $instance = null;
        
        if (is_callable($middleware, true)) {

            /**
             * Parse class constructor parameters
             */
            if(is_string($middleware) && preg_match('/::/', $middleware)) {
                // Middleware is a method

                $method = explode('::', $middleware);
                $reflex = new \ReflectionMethod($method[0], $method[1]);

                $class_reflex = new \ReflectionClass($reflex->class);
                $class_constructor = $class_reflex->getConstructor();

                // Method class has no constructor
                if($class_constructor === null) {

                    // Create instance if method is a non static method
                    if(! $reflex->isStatic()) {
                        $instance = $class_reflex->newInstanceWithoutConstructor();
                    }
                } else {
                    $class_params = $class_constructor->getParameters();
                    $constructor_args = [];

                    // Parse constructor params
                    foreach($class_params as $param) {
                        $type = $param->getType();
                        $type_name = $type ? $type->getName() : '';

                        // Custom param parser
                        $parser = $this->parent->middlewareParamParser($param, $class_reflex, $req, $res);

                        // Check param types
                        if(! is_null($parser) && $parser !== false) {
                            $constructor_args[] = $parser;
                        }
                        else if($type_name === HttpRequest::class) {
                            $constructor_args[] = $req;
                        }
                        else if($type_name === HttpResponse::class) {
                            $constructor_args[] = $res;
                        }
                        else {
                            $constructor_args[] = null;
                        }
                    }

                    // Instanciate
                    $instance = $class_reflex->newInstance(...$constructor_args);

                    // If method is a non static method and have no instance, create one
                    if(!$reflex->isStatic() && !$instance) {
                        $instance = $class_reflex->newInstance();
                    }
                }
            } else {
                // Middleware is an anonymous function
                $reflex = new \ReflectionFunction($middleware);
            }

            /**
             * Parse controller parameters
             */
            $params = $req->params ?? [];
            $reflex_params = $reflex->getParameters();
            $args = [];
            $error_func = null;

            foreach ($reflex_params as $ref_param) {
                $name = strtolower($ref_param->getName());
                $type = $ref_param->getType();
                $type_name = $type ? $type->getName() : '';

                // Custom param parser
                $parser = $this->parent->middlewareParamParser($ref_param, $reflex, $req, $res);

                // Check param types
                if(! is_null($parser) && $parser !== false) {
                    $args[] = $parser;
                }
                else if($type_name === HttpRequest::class) {
                    $args[] = $req;
                }
                else if($type_name === HttpResponse::class) {
                    $args[] = $res;
                }
                else if(\is_a($type_name, \Exception::class, true)) {
                    $args[] = $out;
                    $error_func = $type_name;
                }
                else if(isset($params[$name])) {
                    $args[] = $params[$name];
                }
                else {
                    $args[] = null;
                }
            }

            // Invoke normal or error function
            try {
                if((!is_null($error_func) && $out instanceof \Exception && 
                    ($error_func === get_class($out) || 
                    (\is_a($out, $error_func) && !is_a($out, HttpException::class)) )) ||
                    is_null($error_func) && !($out instanceof \Exception)
                ) {
                    if($reflex instanceof \ReflectionFunction) {
                        $out = $middleware(...$args);
                    } else {
                        $out = $reflex->invokeArgs($instance, $args);
                    }
                }
            } catch(\Exception $ex) {
                // Define error for the next function
                $out = $ex;
            }
        } else if ($middleware instanceof self) {
            $out = $middleware->next($req, $res, $out);
        } else if (($middleware instanceof Router || $middleware instanceof Route) &&
            !($out instanceof \Exception)
        ) {
            $out = $middleware->dispatch($req, $res);
        }

        return $this->next($req, $res, $out);
    }

    public function __get($prop) {
        if($prop === 'controllers') {
            if(!\is_null($this->parent)) {
                return $this->parent->controllers;
            }
            return '';
        }
    }

    /**
     * @param string $controllers Controllers sub namespace
     * @param null|Router|Route $parent Parent router
     */
    public function __construct($parent = null) {
        $this->parent = $parent;
    }
}