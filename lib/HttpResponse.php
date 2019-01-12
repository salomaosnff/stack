<?php
namespace Stack\Lib;

/**
 * HTTP Response
 * @package Stack\Lib
 */
class HttpResponse {

    public $headers = [];
    public $status = null;
    public $body = '';
    public $locals = [];
    public $app;

    /**
     * Add headers to response
     *
     * @param array $headers
     * @return $this
     */
    public function headers(array $headers) {
        $this->headers = array_merge($this->headers, $headers);
        $this->headers = array_filter($this->headers, function($item) { return !empty($item); });
        
        return $this;
    }

    /**
     * Set response status code
     *
     * @param int $code
     * @return $this
     */
    public function status(int $code) {
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
    public function write(string $data, bool $overwrite = false) {
        if($overwrite) $this->body = $data;
        else $this->body .= $data;
        return $this;
    }

    /**
     * Clear the response
     *
     * @return $this
     */
    public function clear() {
        $this->status = null;
        $this->body = '';
        return $this;
    }

    /**
     * Check response type
     *
     * @param string $type
     * @return bool
     */
    public function is(string $type) {
        $type = preg_quote($type, '@');
        return (bool) \preg_match("@$type@", ($this->headers['Content-Type'] ?? ''));
    }

    /**
     * Send response headers
     *
     * @return $this
     */
    public function send_headers () {
        \http_response_code($this->status);
        
        foreach($this->headers as $header => $value){
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
    public function json($data, $status = 200) {
        $body = json_decode($this->body, true) ?? '';
        if(is_array($body)) $data = array_merge($data, $body);
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
    public function text(string $data, $status = 200) {
        return $this
            ->status($status)
            ->headers(['Content-Type' => 'plain/text'])
            ->write($data)
            ;
    }

    /**
     * Respond with an Error
     *
     * @param \Exception $error
     * @param null $info
     * @param int $status
     * @return HttpResponse
     */
    public function error(\Exception $error, $info = null, $status = 200) {
        if ($error instanceof HttpError) {
            $status = $error->getCode();
            $info = $error->info ?? $info;
        } else if ($error instanceof \Exception) {
            $error = new HttpError(HttpError::INTERNAL_SERVER_ERROR, [
                'error' => $error->getMessage(),
                'code' => $error->getCode()
            ]);
        }

        return $this
            ->status($status)
            ->json([
                'message' => $error->getMessage(),
                'code' => $error->getCode(),
                'info' => $error->info ?? $info
            ])
            ;
    }

    /**
     * End the response
     *
     * @param bool $die
     */
    public function end ($die = true) {
        $this->send_headers();
        $this->status = null;
        if ($die) die($this->body);
        echo $this->body;
    }
}