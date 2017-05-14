<?php

/**
 * Describes app entities. Children must employ the following structure:
 * {
 *  'field_name': {
 *      TYPE: TYPE_const,
 *      WIDTH: int,
 *      LABEL: string,
 *      ITEMS: {1:string, 2:string, ...},
 *      COMMENT: string,
 *      CALLBACK: callable,
 *  }, ...
 * }
 *
 * @author stas trefilov
 */

namespace Vertilia\Data;

use Vertilia\Nls\Nls;
use Vertilia\Nls\Text;

class Entity
{
    const TYPE = 1;
    const WIDTH = 2;
    const LABEL = 3;
    const ITEMS = 4;
    const COMMENT = 5;
    const FLAGS = 6;
    const CALLBACK = 7;
    const REQUIRED = 8;

    const TYPE_ALPHA = 1;
    const TYPE_INT = 2;
    const TYPE_DECIMAL = 3;
    const TYPE_BOOL = 4;
    const TYPE_BLOB = 5;
    const TYPE_ENUM = 6;
    const TYPE_SET = 7;

    const FLAG_POSITIVE = 0x01;
    const FLAG_CENTS = 0x02;
    const FLAG_PASSWORD = 0x04;
    const FLAG_UPPERCASE = 0x08;
    const FLAG_UCFIRST = 0x10;
    const FLAG_TEXTAREA = 0x20;
    const FLAG_ASIS = 0x40;

    /** @var Text */
    public $text;
    /** @var Nls */
    protected $nls;

    /** @var array */
    public $fields = [];

    /** @var array */
    public $errors = [];

    /**
     * @param Text $text
     * @param Nls $nls
     */
    public function __construct(Text $text, Nls $nls)
    {
        $this->text = $text;
        $this->nls = $nls;
    }

    /**
     * @param Record $record
     * @param array $fields
     * @return bool
     */
    public function validate(Record $record, array $fields = []): bool
    {
        if (empty($fields)) {
            $fields = $this->fields;
        }
        foreach ($fields as $field => $repo) {
            if (! is_array($repo)) {
                $repo = (is_scalar($repo) and ! is_bool($repo))
                    ? [$repo => true]
                    : [];
            }
            $repo += $this->fields[$field] ?? [];
            $value = isset($record->$field)
                ? (is_string($record->$field) ? trim($record->$field) : $record->$field)
                : null;
        }

        return empty($this->errors);
    }
}
