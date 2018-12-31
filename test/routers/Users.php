<?php

namespace \App\Routers;
use \Stack\Lib\Router;

class UsersRouter extends Router {
    public function __construct () {
        parent::__construct('/users');

        $this->route('/')
            ->get(function($req, $res) {
                return $res->json([
                    ["id" => 1, "name" => "João"],
                    ["id" => 2, "name" => "Maria"],
                ]);
            })
            ->post(function($req, $res) {
                return $res->json(["id" => 2, "name" => "Pedro"]);
            })
            ;

        $this->route('/:id')
            ->get(function($req, $res) {
                return $res->json([
                    "get" => $req->params['id'],
                    ["id" => 1, "name" => "José"],
                ]);
            })
            ->put(function($req, $res) {
                return $res->json([
                    "put" => $req->params['id'],
                    ["id" => 1, "name" => "José"],
                ]);
            })
            ->delete(function($req, $res) {
                return $res->json([
                    "delete" => $req->params['id'],
                    ["id" => 1, "name" => "José"],
                ]);
            })
        ;
    }
}