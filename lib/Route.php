<?php
namespace Stack\Lib;

class Route extends Routeable {
    public function __construct (string $url) {
        parent::__construct($url, 'route');
    }
}