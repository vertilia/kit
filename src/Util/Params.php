<?php

/**
 * Handling the list of parameters
 *
 * @author stas trefilov
 */

namespace Vertilia\Util;

class Params
{
    /** inject a new attributes into the list of attributes or add new value to the existing attribute
     * @param array $params array of attributes (reference)
     * @param string $name  attribute name
     * @param string $value attribute value
     * @param string $sep   separator of attribute values
     */
    public static function add(array &$params, string $name, string $value, string $sep=' ')
    {
        if (! is_array($params)) {
            $params = [$name=>$value];
        } elseif (empty($params[$name])) {
            $params[$name] = $value;
        } elseif (strpos("$sep{$params[$name]}$sep", "$sep$value$sep") === false) {
            $params[$name] .= "$sep$value";
        }
    }

    /** returns the value of the specified attribute and unsets it in the attributes array
     * @param array $params     array of attributes
     * @param string $name      attribute name
     * @param mixed $default    default value to return if $params[$name] is not set
     * @return mixed
     */
    public static function extract(array &$params, string $name, $default=null)
    {
        if (! is_array($params) or ! array_key_exists($name, $params)) {
            return $default;
        }

        $ret = $params[$name];
        unset($params[$name]);

        return $ret;
    }
}
