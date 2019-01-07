<?php
namespace Stack\Lib;

class HttpResponse {
    public $headers = [];
    public $status = null;
    public $body = '';
    public $locals = [];
    public $app;

    public function headers(array $headers) {
        $this->headers = array_merge($this->headers, $headers);
        $this->headers = array_filter($this->headers, function($item) { return !empty($item); });
        
        return $this;
    }

    public function status(int $code) {
        $this->status = max(0, min(599, $code));

        return $this;
    }

    public function write(string $data) {
        $this->body .= $data;
        return $this;
    }

    public function clear() {
        $this->status = null;
        $this->body = '';
        return $this;
    }

    public function is(string $type) {
        $type = preg_quote($type, '@');
        return (bool) \preg_match("@$type@", $this->headers['Content-Type']);
    }

    public function send_headers () {
        \http_response_code($this->status);
        
        foreach($this->headers as $header => $value){
            header("$header: $value", true);
        }

        return $this;
    }

    public function json($data, $status = 200) {
        return $this
            ->status($status)
            ->headers(['Content-Type' => 'application/json'])
            ->write(json_encode($data))
            ;
    }

    public function text(string $data, $status = 200) {
        return $this
            ->status($status)
            ->headers(['Content-Type' => 'plain/text'])
            ->write($data)
            ;
    }

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

    public function end ($die = true) {
        $this->send_headers();
        $this->status = null;
        if ($die) die($this->body);
        echo $this->body;
    }
}