# GenAI Routing

A minimal, dependency-free **pure route matching engine** for PHP, built and tested against **PHP 5.3.29**.

You give it an HTTP method and a request path; it matches them against your configured routes and returns the handler (e.g. `UserController@show`) together with any captured parameters. It does **not** read the request for you and it does **not** dispatch — it only answers the question *"which controller/action handles this request?"*.

---

## Features

- Match by HTTP method + path → returns handler + params, or `false` on no match (your 404 signal).
- Named placeholders: `/user/{id}`.
- Optional regex constraints: `/user/{id:\d+}`.
- Multiple placeholders per route: `/posts/{slug}/comments/{commentId}`.
- Per-method helpers (`get`, `post`, `put`, `patch`, `delete`) plus `any()`.
- **Bulk loading** of a whole route table in one call via `load()` — ideal for a route table built or cached once at boot.
- Handler splitting helpers: `getController()` / `getAction()` for `Controller@action` strings.
- PSR-4 namespaced (`GenAI\Routing\`), with both a Composer mapping and a standalone autoloader.
- 100% PHP 5.3.29 compatible — `array()` syntax only, no features newer than 5.3.

---

## Requirements

- PHP **>= 5.3.0** (verified on 5.3.29).
- No extensions beyond core PCRE.

---

## Installation

### Option A — standalone (no Composer)

Require the bundled autoloader (see `demo/autoload.php` for a ready-made one):

```php
require __DIR__ . '/autoload.php';
```

### Option B — Composer

A PSR-4 mapping is already declared in `composer.json`:

```json
"autoload": { "psr-4": { "GenAI\\Routing\\": "src/" } }
```

Run `composer dump-autoload`, then use Composer's autoloader as usual.

---

## Quick start

```php
require __DIR__ . '/autoload.php';

use GenAI\Routing\Router;

$router = new Router();

$router->get('/users',     'UserController@index');
$router->get('/user/{id}', 'UserController@show');
$router->post('/users',    'UserController@create');

// You supply the method + path (e.g. from your front controller).
$match = $router->match('GET', '/user/5');

if ($match === false) {
    // No route matched — send your 404 response.
    header('HTTP/1.1 404 Not Found');
    exit;
}

$match->getHandler();     // 'UserController@show'
$match->getParams();      // array('id' => '5')
$match->getController();  // 'UserController'
$match->getAction();      // 'show'
$match->getParam('id');   // '5'
```

---

## Loading a whole route table at once

When you collect all controller/action pairs at boot, hand the entire table to the
router with `load()`. Both entry shapes are accepted, so it fits whatever your
compile step produces:

```php
$router->load(array(
    // Positional: array(method, pattern, handler)
    array('GET',  '/users',     'UserController@index'),
    array('GET',  '/user/{id}', 'UserController@show'),
    array('POST', '/users',     'UserController@create'),

    // Associative: 'method' / 'pattern' (or 'path') / 'handler'
    array(
        'method'  => 'GET',
        'pattern' => '/posts/{slug}/comments/{commentId}',
        'handler' => 'CommentController@show',
    ),
));
```

### Caching the route table

The table is a plain array, so you can build it once and cache it to disk with
`var_export()`, skipping the collection work on every request:

```php
// One-time build step (e.g. on deploy or first boot):
file_put_contents(
    $cacheFile,
    '<?php return ' . var_export($routeTable, true) . ';'
);

// Every request afterwards:
$router->load(require $cacheFile);
$match = $router->match($method, $path);
```

---

## Route patterns

| Pattern                               | Matches                          | Captured params                          |
|---------------------------------------|----------------------------------|------------------------------------------|
| `/users`                              | `/users`                         | *(none)*                                 |
| `/user/{id}`                          | `/user/5`, `/user/abc`           | `array('id' => '5')`                     |
| `/user/{id:\d+}`                      | `/user/5` (not `/user/abc`)      | `array('id' => '5')`                     |
| `/posts/{slug}/comments/{commentId}`  | `/posts/hello/comments/9`        | `array('slug' => 'hello', 'commentId' => '9')` |

Notes:

- `{name}` matches a single path segment (`[^/]+`).
- `{name:regex}` constrains the segment with your own regex.
- Literal parts of the pattern are escaped, so a `.` in a URL matches a literal dot.
- Trailing slashes are normalized: `/users/` is treated the same as `/users`.

---

## Matching rules

- **First match wins.** Register/emit more specific routes before more general
  ones (e.g. `/user/{id}/edit` before `/user/{id}`).
- Method comparison is case-insensitive (`get` == `GET`).
- A route registered with `any()` (method `*`) matches every HTTP method.
- `match()` returns a `RouteMatch` on success, or `false` when nothing matched.

---

## API reference

### `GenAI\Routing\Router`

| Method | Description |
|--------|-------------|
| `add($method, $pattern, $handler)` | Register one route. Returns `$this`. |
| `get($pattern, $handler)` | Shortcut for `add('GET', ...)`. |
| `post($pattern, $handler)` | Shortcut for `add('POST', ...)`. |
| `put($pattern, $handler)` | Shortcut for `add('PUT', ...)`. |
| `patch($pattern, $handler)` | Shortcut for `add('PATCH', ...)`. |
| `delete($pattern, $handler)` | Shortcut for `add('DELETE', ...)`. |
| `any($pattern, $handler)` | Register a route matching any method. |
| `load(array $routes)` | Register many routes from a single config array. Returns `$this`. |
| `match($method, $path)` | Returns a `RouteMatch`, or `false` if nothing matched. |

### `GenAI\Routing\RouteMatch`

| Method | Description |
|--------|-------------|
| `getHandler()` | The raw handler as registered (e.g. `'UserController@show'`). |
| `getParams()` | All captured params, e.g. `array('id' => '5')`. |
| `getParam($name, $default = null)` | A single param, or `$default` if absent. |
| `getController()` | Controller part of a `Controller@action` handler. |
| `getAction()` | Action part of a `Controller@action` handler. |

---

## Project layout

```
php-routing/
├── src/
│   ├── Route.php        GenAI\Routing\Route       — one route; compiles pattern -> regex
│   ├── Router.php       GenAI\Routing\Router      — register routes + match()
│   └── RouteMatch.php   GenAI\Routing\RouteMatch  — result: handler + params
├── demo/
│   ├── autoload.php     PSR-4 autoloader (for use without Composer)
│   └── example.php      runnable demo
├── composer.json        PSR-4 mapping GenAI\Routing\ -> src/
└── README.md            this file
```

---

## Running the example

With a local PHP:

```sh
php demo/example.php
```

Against a real PHP 5.3.29 via Docker (no local PHP needed):

```sh
docker run --rm -v "$PWD":/app -w /app devilbox/php-fpm-5.3 php demo/example.php
```

Expected output:

```
GET    /user/5                          -> UserController@show          params={"id":"5"}
GET    /users                           -> UserController@index         params=[]
POST   /users                           -> UserController@create        params=[]
DELETE /user/42                         -> UserController@destroy       params={"id":"42"}
GET    /user/7/edit                     -> UserController@edit          params={"id":"7"}
GET    /posts/hello-world/comments/99   -> CommentController@show       params={"slug":"hello-world","commentId":"99"}
GET    /nope                            -> 404 Not Found
```

---

## PHP 5.3 compatibility notes

The codebase deliberately stays within PHP 5.3:

- `array()` syntax everywhere (no `[]` short array syntax — that is 5.4+).
- Namespaces and closures (both available in 5.3).
- Named regex groups `(?P<name>...)`.
- Avoids everything newer: `[]`, `??`, `?:`-as-shorthand pitfalls, `::class`,
  scalar type hints, traits, and variadics.

