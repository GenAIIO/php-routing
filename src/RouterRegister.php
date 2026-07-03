<?php

namespace GenAI\Routing;

use GenAI\Routing\Route\Definition;
use GenAI\Routing\Util\RouteDumper;

/**
 * The build-time half of the router: where routes are declared.
 *
 * You register every route here (typically at application boot, or from an
 * attribute scanner), then hand the register to the route dumper, which
 * compiles them into a fast, precompiled route table. The runtime Router loads
 * that table and matches against it.
 *
 *   $register = new RouterRegister();
 *   $register->get('/user/{id}', 'UserController@show');   // verb helper
 *   $register->post('/users',    'UserController@create');
 *   $register->set(Definition::of('GET', '/health', 'HealthController@ping')); // pre-built
 *   // ... later: a dumper reads $register->getRoutes() and writes a file.
 *
 * Mirrors ContainerRegister: declaration lives here, resolution lives in the
 * runtime class. Each entry is a Route\Definition, which compiles its pattern to
 * a regex as it is added (build time), so the dumper can emit the compiled form.
 *
 * Compatible with PHP 5.3.29.
 */
class RouterRegister
{
    /** @var Definition[] Declared routes, in registration order (first match wins). */
    private $routes = array();

    /**
     * Register a pre-built route Definition directly.
     *
     * The primitive every other declaration method funnels through. Use it when
     * you already have a Definition in hand — e.g. from an attribute scanner —
     * instead of the method/pattern/handler triple that add() takes.
     *
     * @param Definition $def
     * @return RouterRegister $this, for chaining.
     */
    public function set(Definition $def)
    {
        $this->routes[] = $def;

        return $this;
    }

    /**
     * Declare a route for a single HTTP method (or '*' for any).
     *
     * @param string $method  HTTP method ('GET', 'POST', ...) or '*'.
     * @param string $pattern URL pattern, e.g. '/user/{id}'.
     * @param mixed  $handler Whatever you want returned on a match.
     * @return RouterRegister $this, for chaining.
     */
    public function add($method, $pattern, $handler)
    {
        return $this->set(Definition::of($method, $pattern, $handler));
    }

    /**
     * @param string $pattern
     * @param mixed  $handler
     * @return RouterRegister
     */
    public function get($pattern, $handler)
    {
        return $this->add('GET', $pattern, $handler);
    }

    /**
     * @param string $pattern
     * @param mixed  $handler
     * @return RouterRegister
     */
    public function post($pattern, $handler)
    {
        return $this->add('POST', $pattern, $handler);
    }

    /**
     * @param string $pattern
     * @param mixed  $handler
     * @return RouterRegister
     */
    public function put($pattern, $handler)
    {
        return $this->add('PUT', $pattern, $handler);
    }

    /**
     * @param string $pattern
     * @param mixed  $handler
     * @return RouterRegister
     */
    public function patch($pattern, $handler)
    {
        return $this->add('PATCH', $pattern, $handler);
    }

    /**
     * @param string $pattern
     * @param mixed  $handler
     * @return RouterRegister
     */
    public function delete($pattern, $handler)
    {
        return $this->add('DELETE', $pattern, $handler);
    }

    /**
     * Declare a route that matches any HTTP method.
     *
     * @param string $pattern
     * @param mixed  $handler
     * @return RouterRegister
     */
    public function any($pattern, $handler)
    {
        return $this->add('*', $pattern, $handler);
    }

    /**
     * Every declared route, in registration order — hand this to the dumper.
     *
     * @return Definition[]
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Compile every declared route to PHP source (via the RouteDumper helper).
     *
     * @return string PHP source, starting with "<?php", returning a Closure.
     */
    public function dump()
    {
        return RouteDumper::dump($this->routes);
    }

    /**
     * Compile and write the source to a file. The directory must exist.
     *
     * @param string $path
     * @return int Bytes written.
     * @throws \RuntimeException If the file cannot be written.
     */
    public function dumpToFile($path)
    {
        $bytes = @file_put_contents($path, $this->dump());
        if ($bytes === false) {
            throw new \RuntimeException('Could not write compiled routes to "' . $path . '".');
        }

        return $bytes;
    }
}
