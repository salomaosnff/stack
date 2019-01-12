<?php
namespace Stack\Lib;

/**
 * Class Routeable
 * @package Stack\Lib
 */
abstract class Routeable {

    protected $mode = 'router';
    public $baseURL = '/';
    public $regex = '@^/*$@';
    public $params = [];
    public $controllers = '';

    private $stack_global;
    private $stack_methods = [];

    /**
     * Normalize a URL
     *
     * @param string ...$url
     * @return string|string[]|null
     */
    public static function normalize_url(string...$url) {
        array_unshift($url, '/');

        $url = join('/', $url);
        $url = \preg_replace('@/+@', '/', $url);
        $url = \preg_replace('@(?<=.)/$@', '', $url);

        return $url;
    }

    /**
     * Parse the URL params
     *
     * @param string $url
     * @param bool $end
     * @return array
     */
    private static function parse_url(string $url, bool $end = true) {
        $params = [];
        $regex = ['@^'];

        $url = \preg_replace_callback('@:([\w-_]+)([^\/]*)@', function ($match) use (&$params) {
            $name = $match[1];
            $params[] = $name;

            if ($match[2] === '?') {
                $match[2] = "?([^/]*)/*";
            }
            return empty($match[2]) ? '([^/]+)' : $match[2];
        }, $url);

        if (!$end && $url !== '/') {
            $url .= '\b';
        }

        $regex[] = $url;

        if ($end) {
            $regex[] = '$';
        }

        $regex[] = '@';
        $regex = join('', $regex);

        return [
            'params' => $params,
            'regex' => $regex,
        ];
    }

    /**
     * Test a URL with the regex
     *
     * @param HttpRequest $request
     * @param bool $removeBaseURL
     * @return bool
     */
    protected function test(HttpRequest &$request, bool $removeBaseURL = true) {
        preg_match($this->regex, $request->url, $matches);

        if (count($matches) <= 0) {
            return false;
        }

        if(count($matches) === 1) {
            $matches = [trim(array_shift($matches), '/')];
        } else {
            $matches = array_slice($matches, 1);
        }

        if ($removeBaseURL) {
            $request->url = self::normalize_url(\preg_replace($this->regex, '', $request->url));
        }

        if (empty($request->params)) {
            $request->params = [];
        }

        $params = @array_combine($this->params, $matches);

        if(is_array($params)) {
            $request->params = array_merge($request->params, $params);
        }

        return true;
    }

    /**
     * Middleware registration
     *
     * @param mixed ...$middlewares
     * @return $this
     */
    public function use (...$middlewares) {
        $this->stack_global->use(...$middlewares);
        return $this;
    }

    /**
     * Method registration
     *
     * @param string $method
     * @param array $middlewares
     * @return Routeable
     */
    private function register_method(string $method, array $middlewares): Routeable {
        if (!isset($this->stack_methods[$method])) {
            $this->stack_methods[$method] = new MiddlewareStack($this->controllers);
        }

        $middlewares = array_map(function($middleware) {
            return self::normalize_method($middleware);
        }, $middlewares);

        $this->stack_methods[$method]->use(...$middlewares);
        return $this;
    }

    /**
     * Normalize a method string, remove '@' and place '::'
     * with controllers base namespace
     *
     * @param string|callable|Routeable $method
     * @param string|null $controllers Namespace base para os controladores
     * @return string|string[]|null
     */
    public static function normalize_method($method, $controllers = '') {
        if(is_string($method)) {
            $controllers = is_null($controllers) ? '' : trim("$controllers", "\\");
            $method = preg_replace('/\@+/', '::', $method);
            $method = trim($method, "\\ ");
            return "\\$controllers\\$method";
        }
        return $method;
    }

    /**
     * Call method stack from request
     *
     * @param HttpRequest $request
     * @param HttpResponse $response
     * @return HttpError|null
     */
    private function call_method_stack(HttpRequest &$request, HttpResponse &$response) {
        if (isset($this->stack_methods[$request->method])) {
            return $this->stack_methods[$request->method]->next($request, $response);
        }

        return $this->mode === 'route' ? new HttpError(HttpError::METHOD_NOT_ALLOWED) : null;
    }

    /**
     * Register GET method
     *
     * @param string|callable ...$middlewares
     * @return Routeable
     */
    public function get(...$middlewares): Routeable {
        return $this->register_method('GET', $middlewares);
    }

    /**
     * Register POST method
     *
     * @param string|callable ...$middlewares
     * @return Routeable
     */
    public function post(...$middlewares): Routeable {
        return $this->register_method('POST', $middlewares);
    }

    /**
     * Register PUT method
     *
     * @param string|callable ...$middlewares
     * @return Routeable
     */
    public function put(...$middlewares): Routeable {
        return $this->register_method('PUT', $middlewares);
    }

    /**
     * Register PATCH method
     *
     * @param string|callable ...$middlewares
     * @return Routeable
     */
    public function patch(...$middlewares): Routeable {
        return $this->register_method('PATCH', $middlewares);
    }

    /**
     * Register DELETE method
     *
     * @param string|callable ...$middlewares
     * @return Routeable
     */
    public function delete(...$middlewares): Routeable {
        return $this->register_method('DELETE', $middlewares);
    }

    /**
     * Register HEAD method
     *
     * @param string|callable ...$middlewares
     * @return Routeable
     */
    public function head(...$middlewares): Routeable {
        return $this->register_method('HEAD', $middlewares);
    }

    /**
     * Init
     *
     * @param HttpRequest $request
     * @param HttpResponse $response
     * @return HttpError|HttpResponse|null
     * @throws \ReflectionException
     */
    public function init(
        HttpRequest &$request,
        HttpResponse &$response
    ) {
        $global = $this->stack_global->next($request, $response);

        if ($global instanceof HttpResponse || !is_null($global)) {
            return $global;
        }

        return $this->call_method_stack($request, $response);
    }

    /**
     * @param string $url Route URL
     * @param string $mode Router/Route
     * @param string $controllers Controllers base namespace
     */
    public function __construct(
        string $url = '/',
        string $mode = 'router',
        string $controllers = ''
    ) {
        $this->mode = $mode;
        $this->controllers = $controllers;
        $this->stack_global = new MiddlewareStack($this->controllers);
        $this->baseURL = self::normalize_url($url);

        $url = self::parse_url($this->baseURL, $this->mode === 'route');

        $this->regex = $url['regex'];
        $this->params = $url['params'];
    }
}