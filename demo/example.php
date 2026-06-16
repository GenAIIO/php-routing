<?php

/**
 * Example usage of the route engine.
 *
 * Run from the command line:
 *   php example.php
 */

require __DIR__ . '/autoload.php';

use GenAI\Routing\Router;

$router = new Router();

// A single route table — the kind of array you would build (or cache to a
// file) once when the application boots. Both entry shapes are accepted.
// First match wins, so order specific before general.
$routeTable = array(
    // Positional: array(method, pattern, handler)
    array('GET',    '/users',                          'UserController@index'),
    array('GET',    '/user/{id}',                      'UserController@show'),
    array('GET',    '/user/{id:\d+}/edit',             'UserController@edit'),
    array('POST',   '/users',                          'UserController@create'),
    array('DELETE', '/user/{id}',                      'UserController@destroy'),

    // Associative: 'pattern' (or 'path') keys
    array(
        'method'  => 'GET',
        'pattern' => '/posts/{slug}/comments/{commentId}',
        'handler' => 'CommentController@show',
    ),
);

// Hand the whole table to the router in one call.
$router->load($routeTable);

// --- Pretend these come from the incoming request ------------------------
$requests = array(
    array('GET',    '/user/5'),
    array('GET',    '/users'),
    array('POST',   '/users'),
    array('DELETE', '/user/42'),
    array('GET',    '/user/7/edit'),
    array('GET',    '/posts/hello-world/comments/99'),
    array('GET',    '/nope'),
);

foreach ($requests as $req) {
    list($method, $path) = $req;

    $match = $router->match($method, $path);

    if ($match === false) {
        printf("%-6s %-32s -> 404 Not Found\n", $method, $path);
        continue;
    }

    printf(
        "%-6s %-32s -> %-28s params=%s\n",
        $method,
        $path,
        $match->getHandler(),
        json_encode($match->getParams())
    );

    // Splitting the handler for dispatch:
    //   $controller = $match->getController(); // e.g. 'UserController'
    //   $action     = $match->getAction();     // e.g. 'show'
}
