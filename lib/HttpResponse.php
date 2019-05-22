<?php
namespace Stack\Lib;

/**
 * HTTP Response
 * @package Stack\Lib
 */
class HttpResponse
{

    public $headers = [
        'X-Powered-By' => 'Stack',
    ];
    public $status = null;
    public $body   = '';
    public $locals = [];
    public $app;
    public $finished = false;
    public $viewEngine;

    public function __construct()
    {
        $this->viewEngine = new PhpViewEngine();
    }

    /**
     * Add headers to response
     * @param array $headers
     * @return $this
     */
    public function headers(array $headers)
    {
        $this->headers = array_merge($this->headers, $headers);
        $this->headers = array_filter($this->headers, function ($item) {return !empty($item);});
        return $this;
    }

    /**
     * Set response status code
     *
     * @param int $code
     * @return $this
     */
    public function status(int $code)
    {
        $this->status = max(0, min(599, $code));
        return $this;
    }

    /**
     * Add data to response body
     *
     * @param string $data
     * @param bool $overwrite
     * @return $this
     */
    public function write(string $data, bool $overwrite = false)
    {
        if ($overwrite) {
            $this->body = $data;
        } else {
            $this->body .= $data;
        }

        return $this;
    }

    /**
     * Clear the response
     *
     * @return $this
     */
    public function clear()
    {
        $this->status = null;
        $this->body   = '';
        return $this;
    }

    /**
     * Check response type
     *
     * @param string $type
     * @return bool
     */
    public function is(string $type)
    {
        return mimeTypeIs($this->headers['content-type'] ?? '', $type);
    }

    /**
     * Send response headers
     *
     * @return $this
     */
    public function send_headers()
    {
        \http_response_code($this->status);

        foreach ($this->headers as $header => $value) {
            header("$header: $value", true);
        }

        return $this;
    }

    /**
     * Respond with JSON
     *
     * @param $data
     * @param int $status
     * @return HttpResponse
     */
    public function json($data, $status = 200)
    {
        $body = json_decode($this->body, true) ?? '';
        if (is_array($body)) {
            $data = array_merge($data, $body);
        }

        return $this
            ->status($status)
            ->headers(['Content-Type' => 'application/json'])
            ->write(\json_encode($data), true)
        ;
    }

    /**
     * Respond with TEXT
     *
     * @param string $data
     * @param int $status
     * @return HttpResponse
     */
    public function text(string $data, $status = 200)
    {
        return $this
            ->status($status)
            ->headers(['Content-Type' => 'text/plain;charset=utf8'])
            ->write($data)
        ;
    }

    /**
     * Respond with HTML
     *
     * @param string $data
     * @param int $status
     * @return HttpResponse
     */
    public function html(string $html, $status = 200)
    {
        return $this
            ->status($status)
            ->headers(['Content-Type' => 'text/html;charset=utf8'])
            ->write($html)
        ;
    }

    /**
     * Respond with an Error
     * @param \Exception $error
     * @param null $info
     * @param int $status
     * @return HttpResponse
     */
    public function error(\Exception $error, $info = null, $status = 500)
    {
        $showErrors = !!$this->app->display_errors;

        if ($error instanceof HttpException) {
            $info   = $info ?? $error->info;
            $status = $error->getCode();
            return $this->format([
                'application/json' => function ($res) use ($error, $info, $status, $showErrors) {
                    $json = [
                        'error' => $error->getMessage(),
                        'code'  => $status,
                        'info'  => $info,
                    ];
                    if ($showErrors) {
                        $json['trace'] = $error->getTraceAsString();
                    }

                    return $res->json($json, $status);
                },
                'text/html'        => function ($res) use ($error, $info, $status, $showErrors) {
                    return $res->html(
                        "<h1>$status</h1>" .
                        "<h2>{$error->getMessage()}</h2>" .
                        "<p>" . $this->arrayToHtml($info) . "</p>" .
                        ($showErrors ? "<pre>{$error->getTraceAsString()}</pre>" : ""),
                        $status
                    );
                },
                'text/plain'       => function ($res) use ($error, $info, $status, $showErrors) {
                    return $res->text(
                        "{$error->getCode()}\n" .
                        "{$error->getMessage()}\n" .
                        $this->arrayToPlain($info) .
                        ($showErrors ? "\nStack Trace:\n{$error->getTraceAsString()}" : ""),
                        $status
                    );
                },
            ]);
        }

        if ($showErrors) {
            return $this->status($status)->format([
                'application/json' => function ($res) use ($error, $showErrors) {
                    $json = [
                        'error' => $error->getMessage(),
                        'code'  => $error->getCode(),
                    ];
                    if ($showErrors) {
                        $json['trace'] = $error->getTraceAsString();
                    }

                    return $res->json($json);
                },
                'text/html'        => function ($res) use ($error, $showErrors) {
                    return $res->html(
                        "<h1>Uncaught Error</h1>" .
                        "<h2>{$error->getMessage()}</h2>" .
                        ($showErrors ? "Stack Trace: <pre>{$error->getTraceAsString()}</pre>" : "")
                    );
                },
                'text/plain'       => function ($res) use ($error, $showErrors) {
                    return $res->html(
                        "Uncaught Error\n" .
                        "{$error->getMessage()}\n" .
                        ($showErrors ? "\nStack Trace:\n{$error->getTraceAsString()}" : "")
                    );
                },
            ]);
        }

        return $this->error(new HttpException(HttpException::INTERNAL_SERVER_ERROR));
    }

    /**
     * Responds according to the Accept type os request
     * @param bool $die
     */
    public function format($formats = [], $default = 'text/plain')
    {
        $requestFormats = explode(',', $this->app->request->headers['accept'] ?? '');

        foreach ($requestFormats as $requestType) {
            foreach ($formats as $type => $callback) {
                if (mimeTypeIs($requestType, $type)) {
                    return $callback($this);
                }
            }
        }

        if (isset($formats[$default])) {
            return call_user_func($formats[$default], $this);
        }

        throw new HttpException(HttpException::NOT_ACCEPTABLE);
    }

    /**
     * End the response
     * @param bool $die
     */
    public function end($die = true)
    {
        $this->send_headers();
        $this->status = null;
        if ($die) {
            die($this->body);
        }

        echo $this->body;
    }

    /**
     * Finish stack
     *
     * @return $this
     */
    public function finish()
    {
        $this->finished = true;
        return $this;
    }

    /**
     * Render a view
     */
    public function render($viewName, $data = [])
    {
        if (!($this->viewEngine instanceof ViewEngine)) {
            throw new \Exception('Invalid View Engine!');
        }

        return $this
            ->clear()
            ->write(call_user_func([$this->viewEngine, 'render'], $viewName, $data));
    }

    /**
     * Dies with an immediate error
     *
     * @param \Exception $error
     * @param null $info
     * @param int $status
     */
    public static function _throw(\Exception $error, $info = null, $status = 500)
    {
        ob_clean();
        $response = new self;
        $response->error($error, $info, $status)->end(true);
    }

    /**
     * Flatten multi-dimensional array to html list
     */
    private function arrayToHtml($array)
    {
        if (empty($array)) {
            return "";
        }
        if (!is_array($array)) {
            return $array;
        }
        $output = "<ul>";
        array_walk_recursive($array, function ($val) use (&$output) {
            $output .= "<li>" . $val . "</li>";
        });
        $output .= "</ul>";
        return $output;
    }

    /**
     * Flatten a multi-dimensional array to plain text
     */
    private function arrayToPlain($array)
    {
        if (empty($array)) {
            return "";
        }
        if (!is_array($array)) {
            return $array;
        }
        $output = "\n";
        array_walk_recursive($array, function ($val) use (&$output) {
            $output .= $val . "\n";
        });
        return $output;
    }

}
