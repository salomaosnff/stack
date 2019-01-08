<?php
namespace Stack\Lib;

abstract class Routeable {

    protected $mode = 'router';
    public $baseURL = '/';
    public $regex = '@^/*$@';
    public $params = [];

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

        $matches = array_slice($matches, 1);

        if ($removeBaseURL) {
            $request->url = self::normalize_url(\preg_replace($this->regex, '', $request->url));
        }

        if (empty($request->params)) {
            $request->params = [];
        }

        $request->params = array_merge($request->params, array_combine($this->params, $matches));

        return true;
    }

    public function __construct(
        string $url = '/',
        string $mode = 'router'
    ) {
        $this->mode = $mode;
        $this->stack_global = new MiddlewareStack;
        $this->baseURL = self::normalize_url($url);

        $url = self::parse_url($this->baseURL, $this->mode === 'route');

        $this->regex = $url['regex'];
        $this->params = $url['params'];
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
            $this->stack_methods[$method] = new MiddlewareStack;
        }

        $this->stack_methods[$method]->use(...$middlewares);

        return $this;
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
     * @param callable ...$middlewares
     * @return Routeable
     */
    public function get(callable ...$middlewares): Routeable {
        return $this->register_method('GET', $middlewares);
    }

    /**
     * Register POST method
     *
     * @param callable ...$middlewares
     * @return Routeable
     */
    public function post(callable ...$middlewares): Routeable {
        return $this->register_method('POST', $middlewares);
    }

    /**
     * Register PUT method
     *
     * @param callable ...$middlewares
     * @return Routeable
     */
    public function put(callable ...$middlewares): Routeable {
        return $this->register_method('PUT', $middlewares);
    }

    /**
     * Register PATCH method
     *
     * @param callable ...$middlewares
     * @return Routeable
     */
    public function patch(callable ...$middlewares): Routeable {
        return $this->register_method('PATCH', $middlewares);
    }

    /**
     * Register DELETE method
     *
     * @param callable ...$middlewares
     * @return Routeable
     */
    public function delete(callable ...$middlewares): Routeable {
        return $this->register_method('DELETE', $middlewares);
    }

    /**
     * Register HEAD method
     *
     * @param callable ...$middlewares
     * @return Routeable
     */
    public function head(callable ...$middlewares): Routeable {
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
}