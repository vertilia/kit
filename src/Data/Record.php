<?php

/**
 * Object representing app entity record. Children must define public properties (mapped to DB column names).
 *
 * @author stas trefilov
 */

namespace Vertilia\Data;

class Record
{
    /**
     * @param array $params associative array with field values
     */
    public function __construct(array $params = null)
    {
        if ($params) {
            $this->setValues($params);
        }
    }

    /**
     * assigns values to existing fields
     * @param array $params associative array with field values
     */
    public function setValues(array $params)
    {
        foreach ($params as $field => $value) {
            if (property_exists($this, $field)) {
                $this->$field = $value;
            }
        }
    }
}
