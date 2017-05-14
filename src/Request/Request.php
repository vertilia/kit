<?php

/**
 * Base class for requests handling
 *
 * @author stas trefilov
 */

namespace Vertilia\Request;

class Request
{
    /** @var array */
    public $args = [];

    /**
     * @param array $args
     */
    public function __construct(array $args)
    {
        $this->args = $args;
    }
}
