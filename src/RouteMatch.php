<?php

namespace GenAI\Routing;

/**
 * The result of a successful match: the handler plus any captured params.
 *
 * Convenience accessors split a 'Controller@action' handler string into its
 * controller and action parts.
 *
 * Compatible with PHP 5.3.29.
 */
class RouteMatch
{
    /** @var mixed The matched handler, e.g. 'UserController@show'. */
    private $handler;

    /** @var array Captured route parameters, e.g. array('id' => '5'). */
    private $params;

    /**
     * @param mixed $handler
     * @param array $params
     */
    public function __construct($handler, array $params = array())
    {
        $this->handler = $handler;
        $this->params  = $params;
    }

    /**
     * @return mixed The raw handler exactly as it was registered.
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * @return array All captured parameters.
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param string $name
     * @param mixed  $default Returned when the param is absent.
     * @return mixed
     */
    public function getParam($name, $default = null)
    {
        return isset($this->params[$name]) ? $this->params[$name] : $default;
    }

    /**
     * The controller part of a 'Controller@action' handler.
     *
     * @return string|null Null if the handler is not a string.
     */
    public function getController()
    {
        if (!is_string($this->handler)) {
            return null;
        }

        $parts = explode('@', $this->handler, 2);

        return $parts[0];
    }

    /**
     * The action part of a 'Controller@action' handler.
     *
     * @return string|null Null when no '@action' suffix is present.
     */
    public function getAction()
    {
        if (!is_string($this->handler)) {
            return null;
        }

        $parts = explode('@', $this->handler, 2);

        return isset($parts[1]) ? $parts[1] : null;
    }
}
