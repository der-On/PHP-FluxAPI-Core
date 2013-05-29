<?php

namespace FluxAPI;

/**
 * Class ControllerInterface
 * @package FluxAPI
 */
interface ControllerInterface
{
    public function __construct(\FluxAPI\Api $api);

    /**
     * Returns a list of method names that are considered to be actions known to the API.
     * Override this in your Controller.
     *
     * @return array
     */
    public static function getActions();

    /**
     * Checks for the existance of an action in this controller.
     *
     * @param string $action
     * @return bool - true if action exists, else false
     */
    public static function hasAction($action);

    /**
     * Sets a context variable
     *
     * @param $key
     * @param $value
     */
    public function setContext($key, $value);

    /**
     * Retrieves a context variable.
     *
     * @param $key
     * @return null
     */
    public function getContext($key);

    /**
     * Clears the context
     */
    public function clearContext();
}