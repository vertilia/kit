<?php

/**
 * Handling of app entity stored in database. Children must define $table and $id_fld properties and fill
 * $fields repository
 *
 * @author stas trefilov
 */

namespace Vertilia\Db;

use Vertilia\Data\Entity;
use Vertilia\Data\Record;
use Vertilia\Nls\Nls;
use Vertilia\Nls\Text;
use Vertilia\Util\Logger;
use Vertilia\Util\Misc;

class DbEntity extends Entity
{
    /** @var Db */
    protected $db;
    /** @var Logger */
    protected $log;

    /** @var string */
    protected $table;
    /** @var string */
    protected $id_fld;

   /**
    * @param Text $text
    * @param Nls $nls
    * @param Db $db
    * @param Logger $log
    * @throws DbException
    */
    public function __construct(Text $text, Nls $nls, Db $db, Logger $log)
    {
        parent::__construct($text, $nls);
        $this->db = $db;
        $this->log = $log;

        if (empty($this->table) or empty($this->id_fld)) {
            $e = new DbException($this->text->_('Table name and ID field must be set'));
            $this->log->error(Logger::STD_MESSAGE, [Logger::METHOD=>__METHOD__, Logger::EXCEPTION=>$e]);
            throw $e;
        }
    }

    /**
     * @param string $field
     * @param mixed $value
     * @return string
     */
    public function escapeValue(string $field, $value): string
    {
        if (isset($this->fields[$field])) {
            $repo = $this->fields[$field];
            if (($repo[self::FLAGS] ?? 0) & self::FLAG_ASIS) {
                return $value;
            }
            switch ($repo[self::TYPE] ?? self::TYPE_ALPHA) {
                case self::TYPE_ALPHA:
                    return $this->db->wrapChar(
                        (isset($repo[self::WIDTH]) and mb_strlen($value, $this->nls->charset) > $repo[self::WIDTH])
                            ? mb_substr($value, 0, $repo[self::WIDTH], $this->nls->charset)
                            : $value
                    );
                case self::TYPE_INT:
                    return $this->db->escapeInt($value);
                case self::TYPE_DECIMAL:
                    return $this->db->escapeDecimal($value);
                case self::TYPE_BLOB:
                    $v = Misc::blobPack($value);
                    return $this->db->wrapChar(
                        (isset($repo[self::WIDTH]) and strlen($v) > $repo[self::WIDTH]) // count bytes, not chars!
                            ? null
                            : $v
                    );
                default:
                    return $this->db->wrapChar($value);
            }
        } else {
            return $this->db->wrapChar($value);
        }
    }

    /**
     * @param Record $record
     * @return array
     */
    public function getEscapedValues(Record $record, array $fields = []): array
    {
        if (empty($fields)) {
            $fields = $this->fields;
        }
        $values = [];
        foreach ($fields as $field => $repo) {
            $values[$field] = $this->escapeValue($field, $record->$field ?? null);
        }

        return $values;
    }

    /**
     * @param Record $record
     * @return Record
     * @throws DbException
     */
    public function loadRecord(Record $record): Record
    {
        if (isset($record->{$this->id_fld})) {
            try {
                $r = $this->db->handlerReadPrimary($this->table, $record->{$this->id_fld});
            } catch (DbException $e) {
                $this->log->error(Logger::STD_MESSAGE, [Logger::METHOD=>__METHOD__, Logger::EXCEPTION=>$e]);
                throw $e;
            }
            $record->setValues($r);
        }

        return $record;
    }

    /**
     * @param Record $record
     * @throws DbException
     */
    public function saveRecord(Record $record)
    {
        if ($values = $this->getEscapedValues($record)) {
            $fields = [];
            $duplicates = [];
            foreach ($values as $field => $value) {
                $fields[] = $field;
                if ($field != $this->id_fld) {
                    $duplicates[] = "$field=values($field)";
                }
            }
            try {
                $this->db->dmlDEBUG(Misc::vsprintfArgs(
                    'insert into %table$s(%fields$s)values(%values$s)on duplicate key update %duplicates$s',
                    [
                        'table'=>$this->table,
                        'fields'=>implode(',', $fields),
                        'values'=>implode(',', $values),
                        'duplicates'=>implode(',', $duplicates),
                    ]
                ));
            } catch (DbException $e) {
                $this->log->error(Logger::STD_MESSAGE, [Logger::METHOD=>__METHOD__, Logger::EXCEPTION=>$e]);
                throw $e;
            }

            return $record->{$this->id_fld} ?? $this->db->insertId();
        } else {
            return null;
        }
    }

    /**
     * @param Record $record
     * @throws DbException
     */
    public function deleteRecord(Record $record)
    {
        if (isset($record->{$this->id_fld})) {
            try {
                $this->db->dml(Misc::vsprintfArgs(
                    'delete from %table$s where %id_fld$s=%id$s',
                    [
                        'table'=>$this->table,
                        'id_fld'=>$this->id_fld,
                        'id'=>$this->escapeValue($this->id_fld, $record->{$this->id_fld}),
                    ]
                ));
            } catch (DbException $e) {
                $this->log->error(Logger::STD_MESSAGE, [Logger::METHOD=>__METHOD__, Logger::EXCEPTION=>$e]);
                throw $e;
            }

            return $record->{$this->id_fld};
        } else {
            return null;
        }
    }
}
