<?php

namespace GenAI\Routing;

/**
 * A single route definition: an HTTP method, a URL pattern and a handler.
 *
 * Patterns may contain named placeholders:
 *   /user/{id}                -> {id} matches any segment ([^/]+)
 *   /user/{id:\d+}            -> {id} is constrained to digits
 *   /posts/{slug}/comments    -> multiple placeholders are supported
 *
 * Compatible with PHP 5.3.29 (array() syntax, no new language features).
 */
class Route
{
    /** @var string Upper-cased HTTP method, or '*' to match any method. */
    private $method;

    /** @var string The normalized pattern (leading slash, no trailing slash). */
    private $pattern;

    /** @var mixed The handler, e.g. the string 'UserController@show'. */
    private $handler;

    /** @var string The compiled regular expression used for matching. */
    private $regex;

    /** @var array List of placeholder names found in the pattern. */
    private $paramNames = array();

    /**
     * @param string $method  HTTP method ('GET', 'POST', ...) or '*' for any.
     * @param string $pattern URL pattern, e.g. '/user/{id}'.
     * @param mixed  $handler Whatever you want returned on a match.
     */
    public function __construct($method, $pattern, $handler)
    {
        $this->method  = strtoupper($method);
        $this->pattern = $this->normalize($pattern);
        $this->handler = $handler;
        $this->compile();
    }

    /**
     * @return mixed The configured handler.
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * @return string The original (normalized) pattern.
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * Does this route accept the given HTTP method?
     *
     * @param string $method Upper-cased HTTP method.
     * @return bool
     */
    public function methodMatches($method)
    {
        return $this->method === '*' || $this->method === $method;
    }

    /**
     * Try to match a request path against this route.
     *
     * @param string $path The request path, e.g. '/user/5'.
     * @return array|false  An associative array of params on success
     *                      (e.g. array('id' => '5')), or false on no match.
     */
    public function match($path)
    {
        $path = $this->normalize($path);

        if (!preg_match($this->regex, $path, $matches)) {
            return false;
        }

        $params = array();
        foreach ($this->paramNames as $name) {
            if (isset($matches[$name])) {
                $params[$name] = $matches[$name];
            }
        }

        return $params;
    }

    /**
     * Ensure a leading slash and strip any trailing slash (except for root).
     *
     * @param string $path
     * @return string
     */
    private function normalize($path)
    {
        $path = '/' . ltrim($path, '/');

        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return $path;
    }

    /**
     * Compile the pattern into a regular expression, escaping the literal
     * portions and turning {name} / {name:regex} into named capture groups.
     */
    private function compile()
    {
        $pattern = $this->pattern;
        $names   = array();
        $regex   = '';
        $offset  = 0;

        $found = preg_match_all(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^{}]+))?\}#',
            $pattern,
            $sets,
            PREG_SET_ORDER | PREG_OFFSET_CAPTURE
        );

        if ($found) {
            foreach ($sets as $set) {
                $whole = $set[0][0];
                $pos   = $set[0][1];

                // Literal text before this placeholder, quoted so that regex
                // metacharacters (".", etc.) in the URL are matched literally.
                $literal = substr($pattern, $offset, $pos - $offset);
                $regex  .= preg_quote($literal, '#');

                $name       = $set[1][0];
                $constraint = (isset($set[2][0]) && $set[2][0] !== '')
                    ? $set[2][0]
                    : '[^/]+';

                $regex  .= '(?P<' . $name . '>' . $constraint . ')';
                $names[] = $name;

                $offset = $pos + strlen($whole);
            }
        }

        // Trailing literal text after the last placeholder.
        $regex .= preg_quote(substr($pattern, $offset), '#');

        $this->regex      = '#^' . $regex . '$#';
        $this->paramNames = $names;
    }
}
