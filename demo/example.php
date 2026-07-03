<?php

/**
 * Example usage of the route engine.
 *
 *   build time : declare routes on a RouterRegister, compile to a file
 *   runtime    : load the compiled file into a Router, then match
 *
 * The runtime never compiles patterns — the regexes are baked at build time.
 * Run from the command line:
 *   php example.php
 */

require __DIR__ . '/autoload.php';

use GenAI\Routing\Route\Definition;
use GenAI\Routing\Router;
use GenAI\Routing\RouterRegister;

// --- BUILD TIME: declare routes, then compile ----------------------------
//
// Declaration order does not decide static-vs-dynamic priority: the dumper
// sorts by specificity (static segments beat {placeholders}), so a literal
// route always wins over a placeholder that could also match. Registration
// order only breaks ties between equally-specific routes.

$register = new RouterRegister();

$register
    ->get('/',                                  'IndexController@index')
    ->get('/users',                                  'UserController@index')
    ->get('/user/{id}',                              'UserController@show')
    ->get('/user/{id:\d+}/edit',                     'UserController@edit')
    ->post('/users',                                 'UserController@create')
    ->delete('/user/{id}',                           'UserController@destroy')
    ->get('/posts/{slug}/comments/{commentId}',      'CommentController@show');

// set() registers a pre-built Definition directly — the same path an attribute
// scanner would use.
$register->set(Definition::of('GET', '/health', 'HealthController@ping'));

// Declared placeholder-FIRST on purpose: the compile-time specificity sort still
// makes the static /products/detail win over /products/{name}, so the static
// route is not shadowed despite being registered second.
$register
    ->get('/products/{name}', 'ProductController@show')
    ->get('/products/detail', 'ProductController@detail');

@mkdir(__DIR__ . '/cache', 0777, true);
$file = __DIR__ . '/cache/Router.php';        // class Cache\Router (PSR-4: cache/Router.php)
$register->dumpToFile($file);

echo "--- generated " . basename($file) . " ---\n";
echo file_get_contents($file);
echo "--- end generated ---\n\n";

// --- RUNTIME: the compiled router is a ready subclass ---------------------

$router = new \Cache\Router();

// Pretend these come from incoming requests.
$requests = array(
    array('GET',    '/'),                  // -> IndexController@index (the root route)
    array('GET',    '/user/5'),
    array('GET',    '/users'),
    array('POST',   '/users'),
    array('DELETE', '/user/42'),
    array('GET',    '/user/7/edit'),
    array('GET',    '/posts/hello-world/comments/99'),
    array('GET',    '/health'),
    array('GET',    '/products/detail'),   // -> detail  (static wins over {name})
    array('GET',    '/products/widget'),   // -> show, name=widget
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
