<?php

/**
 * Command line requests handling
 *
 * @author stas trefilov
 */

namespace Vertilia\Request;

class CliRequest extends Request
{
    /** @var array */
    public $options;

    /**
     * @global int $argc
     * @global array $argv
     * @param string $options
     * @param array $longopts
     */
    function __construct(string $options = null, array $longopts = null)
    {
        global $argc, $argv;

        $this->args = $argv;
        if ($options) {
            $this->options = getopt($options, $longopts ?: []);
            if ($this->options) {
                $cnt = 0;
                foreach ($this->options as $k=> $v) {
                    ++$cnt;
                    if ($v !== false) {
                        ++$cnt;
                    }
                }
                if ($cnt and $cnt + 1 < $argc and $this->args[$cnt + 1] == '--') {
                    unset($this->args[$cnt + 1]);
                }
                array_splice($this->args, 1, $cnt, []);
            }
        }
    }
}
