<?php
namespace Stack\Lib;

/**
 * Convert query string to array of parameters
 *
 * @param string $query
 * @return array
 */
function qs_to_array($query = '') {
    $query = \preg_replace('@\?*@', '', $query);
    $result = [];
    $params = explode('&', $query);

    foreach ($params as $param) {
        $param = explode("=", $param);
        $name = $param[0];
        $value = $param[1] ?? null;

        if (empty($name)) {
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

class HttpRequest {

    public $method = null;
    public $url = null;
    public $original_url = null;
    public $query = null;
    public $query_string = null;
    public $body = null;
    public $raw_body = null;
    public $headers = null;
    public $remote_address = null;
    public $params = null;
    public $app = null;

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
        $url = Routeable::normalize_url('/', $url);

        $req = new self;

        $req->method = $_SERVER['REQUEST_METHOD'];
        $req->original_url = preg_replace('@\?.*$@', '', $url);
        $req->url = $url ?? $req->original_url;
        $req->query_string = $_SERVER['QUERY_STRING'] ?? null;
        $req->query = qs_to_array($req->query_string);
        $req->raw_body = file_get_contents('php://input');
        $req->headers = self::normalizeHeaders(getallheaders());
        $req->remote_address = isset($req->headers["x-forwarded-for"])
        ? explode($req->headers["x-forwarded-for"], ",")[0]
        : $_SERVER['REMOTE_ADDR']
        ;

        if ($req->is('json')) {
            $req->body = \json_decode($req->raw_body);
        } else if ($req->is('x-www-form-urlencoded')) {
            $req->body = qs_to_array($req->raw_body);
        } else if ($req->is('multipart/form-data')) {
            $req->body = $_POST;
        }

        return $req;
    }

    /**
     * Check current request type
     *
     * @param $type
     * @return bool
     */
    public function is($type) {
        $mime = preg_quote($type, '@');
        return (bool) preg_match("@$mime@", ($this->headers['content-type'] ?? ''));
    }
}