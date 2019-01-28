<?php

namespace Stack\Lib;

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
    public $controllers = '';

    /**
     * Validate exception or null
     *
     * @param $v
     * @return bool
     */
    public static function __check_value ($v) {
        return is_null($v) || $v instanceof HttpResponse;
    }

    /**
     * Registra middlewares na stack
     *
     * @param callable|Routeable|array $middlewares Lista de middlewares
     * @return MiddlewareStack
     */
    public function use(...$middlewares) {
        $middlewares = array_filter($middlewares, function($middleware) {
            return is_callable($middleware, true) || ($middleware instanceof self) || ($middleware instanceof Router);
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
    public function next (HttpRequest &$req, HttpResponse &$res, $err = null) {
        if (count($this->stack) <= 0) {
            return $err;
        };

        $middleware = array_shift($this->stack);
        $middleware = Routeable::normalize_method($middleware, $this->controllers);

        $reflex = null;
        $instance = null;
        
        if (is_callable($middleware, true)) {

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
                    $class_args = $class_constructor->getNumberOfParameters();

                    if($class_args === 1) {
                        $instance = $class_reflex->newInstance($req);
                    }
                    else if($class_args === 2) {
                        $instance = $class_reflex->newInstance($req, $res);
                    }

                    // If method is a non static method and have no instance, create one
                    if(!$reflex->isStatic() && !$instance) {
                        $instance = $class_reflex->newInstance();
                    }
                }
            } else {
                // Anonymous function
                $reflex = new \ReflectionFunction($middleware);
            }

            $args_len = $reflex->getNumberOfParameters();

            // Return the response if have
            if ($err instanceof HttpResponse) return $err;

            // Is a regular middleware function
            if($err === null) {

                $params = $req->params;
                $reflex_params = $reflex->getParameters();
                $args = [];

                foreach ($reflex_params as $ref_param) {
                    $name = strtolower($ref_param->getName());
                    $type_name = $ref_param->getType() ? $ref_param->getType()->getName() : null;

                    if($type_name && $type_name === HttpRequest::class) {
                        $args[] = $req;
                    }
                    else if($type_name && $type_name === HttpResponse::class) {
                        $args[] = $res;
                    }
                    else if(isset($params[$name])) {
                        $args[] = $params[$name];
                    }
                    else {
                        $args[] = null;
                    }
                }

                // Invoke function
                if($reflex instanceof \ReflectionFunction) {
                    $err = $middleware(...$args);
                } else {
                    $err = $reflex->invokeArgs($instance, $args);
                }

                return $this->next($req, $res, $err);
            }

            // Is a function for error parsing
            else if($args_len === 3 && ($reflex instanceof \ReflectionFunction || $reflex->isStatic())) {
                $err = $middleware($err, $req, $res);
                return $this->next($req, $res, $err);
            }
        } else if ($middleware instanceof self) {
            $err = $middleware->next($req, $res, $err);
        }
        
        return $this->next($req, $res, $err);
    }

    /**
     * @param string $controllers Controllers sub namespace
     */
    public function __construct($controllers = '') {
        $baseNamespace = trim(StackApp::$stack_controllers, '\\');
        if(substr($controllers, 0, 1) === '\\') {
            $baseNamespace = '';
        }
        $controllers = trim($controllers, '\\');
        $this->controllers = '\\' . trim($baseNamespace . '\\' . $controllers, '\\');
    }
}