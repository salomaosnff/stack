<?php
require '../vendor/autoload.php';

use Stack\Lib\{
    Route,
    Router,
    HttpError,
    HttpRequest,
    HttpResponse
};

use Stack\Plugins\OAuth\OAuthPlugin;

var_dump(new OAuthPlugin);

$router = new Router('/api');
$router2 = new Router('/error');

$router2->route('/')->get(function($req, $res) {
    return [
        'name' => 'ROUTER_2_ERROR',
        'error' => 'NOT_FOUND'
    ];
});

$router->use($router2);

$router->use(function($err, $req, $res) {
    return [
        'name' => 'MAIN_ROUTER_ERROR',
        'error' => $err
    ];
});

$router->use(function($err, $req, $res) {
    $res->json([
        'name' => 'final_error',
        'error' => $err
    ]);
});

$req = new HttpRequest;
$res = new HttpResponse;

var_dump("FINAL = ", $router->init($req, $res));
$res->end();