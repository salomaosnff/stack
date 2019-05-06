<?php
namespace Stack\Lib;

/**
 * Class Route
 * @package Stack\Lib
 */
class Route extends Routeable {

    public function __construct (string $url, $controllers = '') {
        parent::__construct($url, 'route', $controllers);
    }

    public function init (HttpRequest &$request, HttpResponse &$response, $err = null) {
        if (!$this->test($request, false)) return null;
        return parent::init($request, $response, $err);
    }
}