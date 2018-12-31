<?php

namespace Stack\Lib;

class MiddlewareStack {
    private $stack = [];

    /**
     * Registra middlewares na stack
     * @param callable|Routeable $middlewares Lista de middlewares
     */
    public function use(...$middlewares) {
        $middlewares = array_filter($middlewares, function($middleware) {
            return is_callable($middleware) || ($middleware instanceof self);
        });

        $this->stack = array_merge($this->stack, $middlewares);
        return $this;
    }

    public static function __check_value($v) {
        return (is_bool($v) && $v === true) || is_null($v);
    }

    private function __next(&...$args) {   
        if (count($this->stack) <= 0) return true;   

        $middleware = $this->stack[0];
        $result = true;

        if (is_callable($middleware)) {
            $func = new \ReflectionFunction($middleware);
            if ($func->getNumberOfParameters() > count($args)) return true;
            $result = call_user_func_array($middleware, $args);
        } else if ($middleware instanceof self) {
            $result = $middleware->flush(...$args);
        }

        if (self::__check_value($result)) {
            array_shift($this->stack);
            return true;
        }

        return $result;
    }

    public function flush(&...$initial_value) {
        $result = null;
        
        while(self::__check_value($result) && count($this->stack) > 0) {
            if (is_array($result)) {
                $result = $this->__next(...$result, ...$initial_value);
            } else {
                $result = $this->__next(...$initial_value);
            }
        }

        return $result;
    }
}