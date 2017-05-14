<?php

/**
 * DB management with read/write replicas
 *
 * @author stas trefilov
 */

namespace Vertilia\Db;

class Db
{
    const MODE_READ = 1;
    const MODE_WRITE = 2;
    const HOST = 'host';
    const USER = 'user';
    const PASS = 'pass';
    const DB = 'db';
    const PORT = 'port';
    const SOCKET = 'socket';
    const FLAGS = 'flags';
    const CHARSET = 'charset';

    /** @var array */
    protected $params_read;
    /** @var array */
    protected $params_write;

    /** @var resource */
    protected $db_read;
    /** @var resource */
    protected $db_write;

    /** @var resource */
    protected $db;
    /** @var int */
    protected $db_mode;

    /** randomly selects one of the hosts for connection
     *
     * @param array $hosts collection of hosts, ex: {
     *  'users1':{'host':'users_host_1', 'user':'app', ...},
     *  'users2':{'host':'users_host_2', 'user':'app', ...},
     *  'users3':{'host':'users_host_3', 'user':'app', ...},
     * }
     * @return array a selected host, like {'host':'users_host_2', 'user':'app', ...}
     */
    public static function selectHostRand(array $hosts): array
    {
        return $hosts[array_rand($hosts)];
    }

    /**
     * @param array $params_read
     * @param array $params_write (optional)
     */
    public function __construct(array $params_read, array $params_write = null)
    {
        $this->params_read = $params_read;
        if (isset($params_write)) {
            $this->params_write = $params_write;
        }
    }

    /**
     * @param int $mode
     * @throws DbException
     */
    protected function connect(int $mode)
    {
        $params = (self::MODE_WRITE == $mode)
            ? $this->params_write
            : $this->params_read;
        if ($link = mysqli_init()) {
            if (isset($params[self::CHARSET])) {
                mysqli_options($link, MYSQLI_SET_CHARSET_NAME, $params[self::CHARSET]);
            }
            if (mysqli_real_connect(
                $link,
                $params[self::HOST] ?? null,
                $params[self::USER] ?? null,
                $params[self::PASS] ?? null,
                $params[self::DB] ?? null,
                $params[self::PORT] ?? null,
                $params[self::SOCKET] ?? null,
                $params[self::FLAGS] ?? null
            )) {
                if (self::MODE_WRITE == $mode) {
                    $this->db_write = $this->db = $link;
                    $this->db_mode = self::MODE_WRITE;
                } else {
                    $this->db_read = $this->db = $link;
                    $this->db_mode = self::MODE_READ;
                }
            } else {
                throw new DbException('Error connecting ('.mysqli_connect_errno().'): '.mysqli_connect_error());
            }
        } else {
            throw new DbException('Error initializing a DB connection');
        }
    }

    /**
     * @param int $mode
     * @return int
     * @throws DbException
     */
    public function setMode(int $mode): int
    {
        // shortcut if already set
        if ($this->db_mode == $mode) {
            return $this->db_mode;
        }

        // set mode or connect if needed
        if (self::MODE_WRITE == $mode) {
            if (isset($this->db_write)) {
                $this->db = $this->db_write;
                $this->db_mode = self::MODE_WRITE;
            } elseif (empty($this->params_write)) {
                throw new DbException('Write partition is not set');
            } else {
                $this->connect(self::MODE_WRITE);
            }
        } elseif (empty($this->db_read)) {
            $this->connect(self::MODE_READ);
        } else {
            $this->db = $this->db_read;
            $this->db_mode = self::MODE_READ;
        }

        return $this->db_mode;
    }

    /** executes a sql statement and fetches only one record in associative mode
     *
     * @param string $sql
     * @return array
     * @throws DbException
     */
    public function fetchRow(string $sql): array
    {
        // connect to read replica by default
        if (empty($this->db_mode)) {
            $this->setMode(self::MODE_READ);
        }

        if ($result = mysqli_query($this->db, $sql)) {
            $row = mysqli_fetch_assoc($result);
            mysqli_free_result($result);
            return $row;
        } else {
            throw new DbException(mysqli_error($this->db).'; SQL: '.$sql);
        }
    }

    /** executes a sql statement and fetches all records into a hash array. each
     * record must consist of just two columns -- key(first) and value(second)
     *
     * @param string $sql
     * @return array
     * @throws DbException
     */
    public function fetchList(string $sql): array
    {
        // connect to read replica by default
        if (empty($this->db_mode)) {
            $this->setMode(self::MODE_READ);
        }

        if ($result = mysqli_query($this->db, $sql)) {
            $lst = [];
            foreach (mysqli_fetch_all($result, MYSQLI_NUM)?:[] as $row) {
                $lst[$row[0]] = $row[1];
            }
            mysqli_free_result($result);
            return $lst;
        } else {
            throw new DbException(mysqli_error($this->db).'; SQL: '.$sql);
        }
    }

    /** executes a sql statement and fetches all records into a hash. <i>$key</i>
     * column is considered a key in the returned list and the corresponding row
     * is a corresponding value
     *
     * @param string $sql
     * @param string $key
     * @return array
     * @throws DbException
     */
    public function fetchHash(string $sql, string $key): array
    {
        // connect to read replica by default
        if (empty($this->db_mode)) {
            $this->setMode(self::MODE_READ);
        }

        if ($result = mysqli_query($this->db, $sql)) {
            $hash = [];
            foreach (mysqli_fetch_all($result, MYSQLI_ASSOC)?:[] as $row) {
                $hash[$row[$key]] = $row;
            }
            mysqli_free_result($result);
            return $hash;
        } else {
            throw new DbException(mysqli_error($this->db).'; SQL: '.$sql);
        }
    }

    /** executes a sql statement and fetches all records into an array
     *
     * @param string $sql
     * @return array
     * @throws DbException
     */
    public function fetchArray(string $sql): array
    {
        // connect to read replica by default
        if (empty($this->db_mode)) {
            $this->setMode(self::MODE_READ);
        }

        if ($result = mysqli_query($this->db, $sql)) {
            $lst = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_free_result($result);
            return $lst?:[];
        } else {
            throw new DbException(mysqli_error($this->db).'; SQL: '.$sql);
        }
    }

    /** access database using low level HANDLER statement via primary key to fetch
     * one row in associative mode
     *
     * @param string $table         table name
     * @param int|string|array $pk  primary key value or array for multiple
     *                              columns key. keys must be properly escaped
     * @return array hash with the row information
     */
    public function handlerReadPrimary(string $table, $pk): array
    {
        return $this->handlerReadIndex($table, '`PRIMARY`', $pk);
    }

    /** access database using low level HANDLER statement via specified index to
     * fetch one row in associative mode
     *
     * @param string $table             table name
     * @param string $index             table index name
     * @param int|string|array $value   index scalar value or array of scalars
     *                                  for multiple columns key. keys must be
     *                                  properly escaped
     * @return array hash with the row information
     * @throws DbException
     */
    public function handlerReadIndex(string $table, string $index, $value): array
    {
        // connect to read replica by default
        if (empty($this->db_mode)) {
            $this->setMode(self::MODE_READ);
        }

        $key = implode(',', (array)$value);
        if (mysqli_query($this->db, "handler $table open")) {
            if ($result = mysqli_query($this->db, "handler $table read $index = ($key)")) {
                $row = mysqli_fetch_assoc($result);
                mysqli_query($this->db, "handler $table close");
                return $row;
            } else {
                throw new DbException(mysqli_error($this->db)."; TABLE: $table($index); VALUE: $key");
            }
        } else {
            throw new DbException(mysqli_error($this->db)."; TABLE: $table($index); VALUE: $key");
        }
    }

    /** executes a DML sentence
     *
     * @param string $sql
     * @return int number of affected rows
     */
    public function dml(string $sql): int
    {
        // connect to write replica by default
        if (empty($this->db_mode)) {
            $this->setMode(self::MODE_WRITE);
        }

        if (mysqli_query($this->db, $sql)) {
            return mysqli_affected_rows($this->db);
        } else {
            throw new DbException(mysqli_error($this->db).'; SQL: '.$sql);
        }
    }

    public function dmlDEBUG(string $sql): int
    {
        error_log(__METHOD__.'(): '.$sql);
        return $this->dml($sql);
    }

    /** @return int last insert id */
    public function insertId(): int
    {
        return $this->db ? mysqli_insert_id($this->db) : null;
    }

    /** if set returns escaped integer value, otherwise 'NULL'
     *
     * @param mixed $value
     * @return string
     */
    public function escapeInt($value): string
    {
        return isset($value) && is_scalar($value)
            ? (int)$value
            : 'null';
    }

    /** if set returns escaped integer value, otherwise 'NULL'
     *
     * @param mixed $value
     * @return string
     */
    public function escapeDecimal($value): string
    {
        return isset($value) && is_scalar($value)
            ? (float)$value
            : 'null';
    }

    /** if set returns escaped string value, otherwise NULL
     *
     * @param string $value
     * @return string
     */
    public function wrapChar(string $value): string
    {
        return isset($value)
            ? "'".mysqli_real_escape_string($this->db, $value)."'"
            : 'null';
    }
}
