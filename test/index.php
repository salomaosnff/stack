<?php
require '../vendor/autoload.php';

use \Stack\Lib\{
    Router,
    StackApp
};
use \App\Routers\{
    UserRouter
};
$app = new StackApp();

var_dump(new \App\Routers\UsersRouter);

$app->use('\App\Routers\UsersRouter');
$app->init();