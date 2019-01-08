<?php
namespace Stack\Lib;

class Route extends Routeable {

    public function __construct (string $url) {
        parent::__construct($url, 'route');
    }

    public function init (HttpRequest &$request, HttpResponse &$response) {
        if (!$this->test($request, false)) return false;
        return parent::init($request, $response);
    }
}