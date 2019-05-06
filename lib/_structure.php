<?php

echo '<3';

$root = [
  new Router('/api', [
    function() {

    },
    new Router('/pessoas', [
      new Route('/', [
        'GET' => [],
        'POST' => [],
        'PUT' => []
      ]),
      new Router('/amigos', [
        function() {

        },
        new Route('/', [
          'GET' => [],
          'POST' => []
        ]),  // Lista de amigos
      ])
    ]),
    function() {
      
    }
  ]), 
];