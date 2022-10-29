<?php

namespace illum\Routing;

/**
 * Middleware
 *
 * @package Leaf
 * @author  Michael Darko
 * @since   1.5.0
 */
abstract class Middleware
{

    /**
     * @var mixed Reference to the next downstream middleware
     */
    protected $next;

    /**
     * Set next middleware
     *
     * This method injects the next downstream middleware into
     * this middleware so that it may optionally be called
     * when appropriate.
     *
     * @param Middleware $nextMiddleware
     */
    final public function setNextMiddleware(Middleware $nextMiddleware)
    {
        $this->next = $nextMiddleware;
    }

    /**
     * Get next middleware
     *
     * This method retrieves the next downstream middleware
     * previously injected into this middleware.
     *
     * @return Middleware
     */
    final public function getNextMiddleware(): Middleware
    {
        return $this->next;
    }

    /**
     * Call the next middleware
     */
    final public function next()
    {
        $nextMiddleware = $this->next;

        if (!$nextMiddleware) {
            return;
        }

        $nextMiddleware->call();
    }

    /**
     * Call
     *
     * Perform actions specific to this middleware and optionally
     * call the next downstream middleware.
     */
    abstract public function call();
}