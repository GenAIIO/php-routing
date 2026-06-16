<?php

namespace GenAI\Routing;

/**
 * The route engine.
 *
 * Register routes, then call match($method, $path). On a hit you get back a
 * RouteMatch (handler + captured params); on a miss you get false.
 *
 *   $router = new Router();
 *   $router->get('/user/{id}', 'UserController@show');
 *
 *   $m = $router->match('GET', '/user/5');
 *   $m->getHandler();  // 'UserController@show'
 *   $m->getParams();   // array('id' => '5')
 *
 * Compatible with PHP 5.3.29.
 */
class Router
{
    /** @var Route[] */
    private $routes = array();

    /**
     * Register a route for a single HTTP method (or '*' for any).
     *
     * @param string $method
     * @param string $pattern
     * @param mixed  $handler
     * @return Router $this, for chaining.
     */
    public function add($method, $pattern, $handler)
    {
        $this->routes[] = new Route($method, $pattern, $handler);

        return $this;
    }

    /**
     * @param string $pattern
     * @param mixed  $handler
     * @return Router
     */
    public function get($pattern, $handler)
    {
        return $this->add('GET', $pattern, $handler);
    }

    /**
     * @param string $pattern
     * @param mixed  $handler
     * @return Router
     */
    public function post($pattern, $handler)
    {
        return $this->add('POST', $pattern, $handler);
    }

    /**
     * @param string $pattern
     * @param mixed  $handler
     * @return Router
     */
    public function put($pattern, $handler)
    {
        return $this->add('PUT', $pattern, $handler);
    }

    /**
     * @param string $pattern
     * @param mixed  $handler
     * @return Router
     */
    public function patch($pattern, $handler)
    {
        return $this->add('PATCH', $pattern, $handler);
    }

    /**
     * @param string $pattern
     * @param mixed  $handler
     * @return Router
     */
    public function delete($pattern, $handler)
    {
        return $this->add('DELETE', $pattern, $handler);
    }

    /**
     * Register a route that matches any HTTP method.
     *
     * @param string $pattern
     * @param mixed  $handler
     * @return Router
     */
    public function any($pattern, $handler)
    {
        return $this->add('*', $pattern, $handler);
    }

    /**
     * Register many routes at once from a single config array.
     *
     * Designed for feeding a route table that you build (or cache) once at
     * application boot. Each entry may be either:
     *
     *   Positional:   array('GET', '/user/{id}', 'UserController@show')
     *   Associative:  array(
     *                     'method'  => 'GET',
     *                     'pattern' => '/user/{id}',   // 'path' also accepted
     *                     'handler' => 'UserController@show',
     *                 )
     *
     * Example:
     *   $router->load(array(
     *       array('GET',  '/users',     'UserController@index'),
     *       array('GET',  '/user/{id}', 'UserController@show'),
     *       array('POST', '/users',     'UserController@create'),
     *   ));
     *
     * @param array $routes A list of route definitions.
     * @return Router $this, for chaining.
     * @throws \InvalidArgumentException When an entry is malformed.
     */
    public function load(array $routes)
    {
        foreach ($routes as $index => $route) {
            if (!is_array($route)) {
                throw new \InvalidArgumentException(
                    'Route #' . $index . ' must be an array.'
                );
            }

            // Positional form: array(method, pattern, handler).
            if (array_key_exists(0, $route)) {
                if (count($route) < 3) {
                    throw new \InvalidArgumentException(
                        'Route #' . $index
                        . ' must be array(method, pattern, handler).'
                    );
                }

                $this->add($route[0], $route[1], $route[2]);
                continue;
            }

            // Associative form.
            $method  = isset($route['method']) ? $route['method'] : null;
            $handler = isset($route['handler']) ? $route['handler'] : null;

            if (isset($route['pattern'])) {
                $pattern = $route['pattern'];
            } elseif (isset($route['path'])) {
                $pattern = $route['path'];
            } else {
                $pattern = null;
            }

            if ($method === null || $pattern === null || $handler === null) {
                throw new \InvalidArgumentException(
                    "Route #" . $index . " needs 'method', 'pattern' (or "
                    . "'path') and 'handler'."
                );
            }

            $this->add($method, $pattern, $handler);
        }

        return $this;
    }

    /**
     * Match an incoming request against the registered routes.
     *
     * The first route that matches both the method and the path wins, so
     * register more specific routes before more general ones.
     *
     * @param string $method The request method, e.g. 'GET' (case-insensitive).
     * @param string $path   The request path, e.g. '/user/5'.
     * @return RouteMatch|false RouteMatch on success, false on no match (404).
     */
    public function match($method, $path)
    {
        $method = strtoupper($method);

        foreach ($this->routes as $route) {
            if (!$route->methodMatches($method)) {
                continue;
            }

            $params = $route->match($path);
            if ($params !== false) {
                return new RouteMatch($route->getHandler(), $params);
            }
        }

        return false;
    }
}
