<?php
namespace Stack\Lib;

/**
 * HTTP Request
 * @package Stack\Lib
 */
class HttpRequest {

    public $method = '';
    public $url = '';
    public $original_url = '';
    public $query = [];
    public $query_string = '';
    public $body = [];
    public $raw_body = '';
    public $files = [];
    public $headers = [];
    public $remote_address = null;
    public $params = [];
    public $app = null;
    public $oauth = null;
    public $oauth_request = null;
    public $auth = null;

    /**
     * Pass every header name to lowercase
     *
     * @param $headers
     * @return array
     */
    private static function normalizeHeaders($headers) {
        $result = [];
        foreach ($headers as $name => $value) {
            $result[strtolower($name)] = $value;
        }
        return $result;
    }

    /**
     * Capture the current HTTP request
     *
     * @return HttpRequest
     */
    public static function get_current() {
        $url = isset($_GET['$route'])
        ? filter_input(\INPUT_GET, '$route', \FILTER_SANITIZE_STRING)
        : $_SERVER['REQUEST_URI']
        ;
        $url = normalize_url('/', $url);

        $req = new self;

        $req->method = $_SERVER['REQUEST_METHOD'];
        $req->original_url = preg_replace('@\?.*$@', '', $url);
        $req->url = $url ?? $req->original_url;
        $req->query_string = $_SERVER['QUERY_STRING'] ?? null;

        if(! is_null($req->query_string)) {
            $req->query_string = urldecode(preg_replace('@^(.*/)@', '', $req->query_string));
            if(substr($req->query_string, 0, 1) === '&') {
                $req->query_string = substr($req->query_string, 1);
            }
        }

        $req->query = self::qs_to_array($req->query_string);
        $req->raw_body = file_get_contents('php://input');
        $req->headers = self::normalizeHeaders(getallheaders());
        $req->remote_address = isset($req->headers["x-forwarded-for"])
        ? explode($req->headers["x-forwarded-for"], ",")[0]
        : $_SERVER['REMOTE_ADDR']
        ;

        $req->files = array_map(function($file) {
            return new FileRequest($file);
        }, $_FILES);

        if ($req->is('json')) {
            $req->body = (array) \json_decode($req->raw_body, true);
        } else if ($req->is('x-www-form-urlencoded')) {
            $req->body = (array) self::qs_to_array(urldecode($req->raw_body));
        } else if ($req->is('multipart/form-data')) {
            $req->body = (array) \json_decode(json_encode($_POST), true);
        }

        return $req;
    }

    /**
     * Check current request type
     * @param $type
     * @return bool
     */
    public function is($type) {
        return mimeTypeIs($this->headers['content-type'] ?? '', $type);
    }

    /**
     * Check current request accept type
     * @param $type
     * @return bool
     */
    public function accept($type) {
        return mimeTypeIs($this->headers['accept'] ?? '', $type);
    }

    /**
     * Check if has a param in the body
     * @param string ...$keys
     * @return bool
     */
    public function hasQuery(string ...$keys) {
        $input = $this->query ?? [];

        foreach ($keys as $val) {
            if(! in_array($val, array_keys($input))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if has a param in the body
     * @param string ...$keys
     * @return bool
     */
    public function hasBody(string ...$keys) {
        $input = $this->body ?? [];

        foreach ($keys as $val) {
            if(! in_array($val, array_keys($input))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if has a file
     * @param string ...$keys
     * @return bool
     */
    public function hasFile(string ...$keys) {
        $input = $this->files ?? [];

        foreach ($keys as $val) {
            if(! in_array($val, array_keys($input))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if has a param
     * @param string ...$keys
     * @return bool
     */
    public function hasParam(string ...$keys) {
        $input = $this->params ?? [];

        foreach ($keys as $val) {
            if(! in_array($val, array_keys($input))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if has any of given keys in query
     * @param string ...$keys
     * @return bool
     */
    public function hasAnyQuery(string ...$keys) {
        $input = $this->query ?? [];

        foreach ($keys as $val) {
            if(in_array($val, array_keys($input))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if has any of given keys in body
     * @param string ...$keys
     * @return bool
     */
    public function hasAnyBody(string ...$keys) {
        $input = $this->body ?? [];

        foreach ($keys as $val) {
            if(in_array($val, array_keys($input))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if has any file
     * @param string ...$keys
     * @return bool
     */
    public function hasAnyFile(string ...$keys) {
        $input = $this->files ?? [];

        foreach ($keys as $val) {
            if(in_array($val, array_keys($input))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if has any param
     * @param string ...$keys
     * @return bool
     */
    public function hasAnyParam(string ...$keys) {
        $input = $this->params ?? [];

        foreach ($keys as $val) {
            if(in_array($val, array_keys($input))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get param from body
     * @param $key
     * @return null|mixed
     */
    public function form($key) {
        if($this->hasBody($key)) {
            return $this->body[$key];
        }
        return null;
    }

    /**
     * Get param from query
     * @param $key
     * @return null|string
     */
    public function query($key) {
        if($this->hasQuery($key)) {
            return $this->query[$key];
        }
        return null;
    }

    /**
     * Get file from request
     * @param $key
     * @return null|FileRequest
     */
    public function file($key) {
        if($this->hasFile($key)) {
            return $this->files[$key];
        }
        return null;
    }

    /**
     * Get file from request
     * @param $key
     * @return null|string
     */
    public function param($key) {
        if($this->hasParam($key)) {
            return $this->params[$key];
        }
        return null;
    }

    /**
     * Create a Base64 File
     * @param string $reference
     * @param string $field_name
     * @param string $field_data
     * @return HttpRequest
     * @throws \Exception
     */
    public function createBase64File(string $reference, string $field_name, string $field_data) {
        $name = $this->form($field_name);
        $data = $this->form($field_data);
        $file = FileRequest::fromBase64($data, $name);

        if(! $file) {
            throw new \Exception('invalid_base64_image');
        }
        if($this->hasFile($name)) {
            throw new \Exception('file_already_exists');
        }

        $this->files[$reference] = $file;
        return $this;
    }

    /**
     * Convert query string to array of parameters
     * @param string $query
     * @return array
     */
    public static function qs_to_array($query = '') {
        $query = \preg_replace('@\?*@', '', $query);
        $result = [];
        $params = explode('&', $query);

        foreach ($params as $param) {
            $param = explode("=", $param);
            $name = $param[0];
            $value = $param[1] ?? null;

            if (empty($name) || $name === '$route') {
                continue;
            }

            if (isset($result[$name])) {
                if (is_array($result[$name])) {
                    $result[$name][] = $value;
                } else {
                    $result[$name] = array($result[$name], $value);
                }
            } else {
                $result[$name] = $value;
            }
        }

        return $result;
    }
}