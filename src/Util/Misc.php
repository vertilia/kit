<?php

/**
 * Miscellanous static methods without special attribution to other classes
 *
 * @author stas trefilov
 */

namespace Vertilia\Util;

use InvalidArgumentException;

class Misc
{
    /** add $n months to the $date_begin considering not skipping short months
     *
     * @param string $date_begin    date that need to be incremented
     * @param int $n                number of months to add
     * @param string $date_base     initial date to trim to (only days part is used)
     * @return string               incremented date in YYYY-MM-DD format
     *
     * @assert('2013-01-01', 1) == '2013-02-01'
     * @assert('2013-01-31', 1) == '2013-02-28'
     * @assert('2013-02-28', 1) == '2013-03-28'
     * @assert('2013-02-28', 1, '2013-01-31') == '2013-03-31'
     * @assert('2013-02-28', 1, '2013-01-29') == '2013-03-29'
     * @assert('2013-02-28', 2, '2013-01-29') == '2013-04-29'
     */
    public static function addMonths(string $date_begin, int $n = 1, string $date_base = null): string
    {
        if (empty($date_base)) {
            $date_base = $date_begin;
        }

        $date_next = date('Y-m-d', strtotime("$date_begin +$n month"));

        if (substr($date_next, 8, 2) != substr($date_begin, 8, 2)) {
            return date('Y-m-d', strtotime("$date_next last day of previous month"));
        } elseif (substr($date_next, 8, 2) != substr($date_base, 8, 2)) {
            return substr($date_next, 0, 8).
                min((int)date('t', strtotime($date_next)), (int)substr($date_base, 8, 2));
        } else {
            return $date_next;
        }
    }

    /** serializes the value if it is not a scalar. long values (>127 bytes) are gzdeflate-d
     *
     * @param mixed $value value to pack
     * @return string compressed blob value
     */
    public static function blobPack($value): string
    {
        // serialize non-scalar values
        if (isset($value)
            and ! is_scalar($value)
        ) {
            $value = ' s:'.serialize($value);
        }

        // compress long values
        if (strlen($value) > 127) {
            $value = ' z:'.gzdeflate($value);
        }

        return $value;
    }

    /** restores blob value packed with blobPack()
     *
     * @param string $blob blobPack-ed value
     * @return mixed original value
     */
    public static function blobUnpack(string $blob)
    {
        // uncompress value if needed
        if (substr($blob, 0, 3) == ' z:') {
            $blob = gzinflate(substr($blob, 3));
        }

        //unserialize value if needed
        if (substr($blob, 0, 3) == ' s:') {
            $blob = unserialize(substr($blob, 3));
        }

        return $blob;
    }

    /** converts numbers from shorthand form (like '1K') to an integer
     *
     * @param string $size_str  shorthand size string to convert
     * @return int integer value in bytes
     *
     * @assert('15') == 15
     * @assert('1K') == 1024
     * @assert('2k') == 2048
     * @assert('1M') == 1048576
     * @assert('1g') == 1073741824
     */
    public static function convertSize(string $size_str): int
    {
        switch (substr($size_str, -1)) {
            case 'M': case 'm':
                return (int)$size_str << 20;
            case 'K': case 'k':
                return (int)$size_str << 10;
            case 'G': case 'g':
                return (int)$size_str << 30;
            default:
                return (int)$size_str;
        }
    }

    /** returns number of days between 2 dates
     *
     * @param string $date_start    first date of period (yyyy-mm-mm)
     * @param string $date_end      last date of period (yyyy-mm-dd)
     * @return int
     *
     * @assert('2013-01-01', '2013-01-01') == 1
     * @assert('2013-01-01', '2013-01-31') == 31
     * @assert('2013-01-01', '2013-12-31') == 365
     */
    public static function daysInPeriod(string $date_start, string $date_end): int
    {
        return round((strtotime("$date_end +1 day") - strtotime($date_start))/86400);
    }

    /** assembles standard address representation from different parts. resulting
     * address is in the following form:
     * [street]
     * [postal] [city] [country]
     *
     * @param string $street    address street
     * @param string $postal    address postal code
     * @param string $city      address city
     * @param string $country   address country
     * @return string       standard address representation
     *
     * @assert('Street', 'Postal', 'City', 'Country') == "Street\nPostal City Country"
     * @assert('Street', 'Postal', 'City', null) == "Street\nPostal City"
     * @assert('Street', null, 'City', null) == "Street\nCity"
     * @assert('Street', null, null, null) == "Street"
     * @assert(null, null, 'City', null) == "City"
     */
    public static function formatAddress(string $street, string $postal, string $city, string $country): string
    {
        return self::joinWs(array("\n", $street, array(' ', $postal, $city, $country)));
    }

    /** html-formatted string with some bb-style formatting convertion
     *
     * @param string $text  bb-style formatted string (recognizes *bold*, /italic/,
     *                      ---header lines---, lines started with dash are bulleted)
     * @return string
     *
     * @assert("line") == "<p>line</p>"
     * @assert("line *bold* line") == "<p>line <b>bold</b> line</p>"
     * @assert("line *bold* /italic/ line") == "<p>line <b>bold</b> <i>italic</i> line</p>"
     * @assert("---heading line---\nline") == "<h5>heading line</h5>\n<p>line</p>"
     * @assert("-line 1\n-line 2") == "<li>line 1</li>\n<li>line 2</li>"
     */
    public static function formatPreview(string $text): string
    {
        return preg_replace(
            array('#&#', '#<#', '#>#',
                '#/([^/\r\n]*)/#m', '#\*([^*\\r\\n]*)\*#m',
                '#^#m', '#$#m',
                '#^<p>---(.*)---</p>$#m',
                '#^<p>-(.*)</p>$#m'
            ),
            array('&amp;', '&lt;', '&gt;',
                '<i>$1</i>', '<b>$1</b>',
                '<p>', '</p>',
                '<h5>$1</h5>',
                '<li>$1</li>'
            ),
            $text
        );
    }

    /** return the formatted tel number or the original string
     *
     * @param string $tel
     * @return string
     *
     * @assert("01.23.45.67.89") == "01 23 45 67 89"
     * @assert("+33-1-23-45-67-89") == "+33 1 23 45 67 89"
     * @assert("T.+33-1-23-45-67-89") == "T.+33-1-23-45-67-89"
     */
    public static function formatTel(string $tel): string
    {
        $t = str_replace(array(' ', '.', '-', '(0)'), '', $tel);
        $m = [];
        if (preg_match('/^(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/', $t, $m)) {
            return "$m[1] $m[2] $m[3] $m[4] $m[5]";
        } elseif (preg_match('/^\+?\(?(\d{2})\)?(\d)(\d{2})(\d{2})(\d{2})(\d{2})$/', $t, $m)) {
            return "+$m[1] $m[2] $m[3] $m[4] $m[5] $m[6]";
        } else {
            return $tel;
        }
    }

    /** returns maximum allowed size for uploaded file in bytes
     *
     * @return int
     */
    public static function getMaxUploadSize(): int
    {
        return min(
            self::convertSize(ini_get('upload_max_filesize')),
            self::convertSize(ini_get('post_max_size'))
        );
    }

    /** returns human-readable amount rounded to 3 meaningful numbers with appropriate suffix (T,G,M,K)
     *
     * @param int $amount   amount to convert
     * @param string $order an order to use, one of (T,G,M,K). if provided, no
     *                      suffix is appended
     * @return string value like '150', '8.37K', '15M', '374G'
     *
     * @assert(20) == '20'
     * @assert(999) == '999'
     * @assert(1000) == '1K'
     * @assert(2000) == '2K'
     * @assert(2100) == '2.1K'
     * @assert(2150) == '2.15K'
     * @assert(2157) == '2.16K'
     * @assert(21573) == '21.6K'
     * @assert(-21573) == '-21.6K'
     * @assert(2000, 'K') == '2'
     * @assert(2157, 'K') == '2.16'
     */
    public static function humanFloat(int $amount, string $order=null): string
    {
        $amount_abs = abs($amount);
        switch ($order) {
            case 'k':
            case 'K':
                return $amount_abs >= 100000
                    ? round($amount/1000)
                    : ($amount_abs >= 10000
                        ? round($amount/1000, 1)
                        : round($amount/1000, 2)
                    );

            case 'm':
            case 'M':
                return $amount_abs >= 100000000
                    ? round($amount/1000000)
                    : ($amount_abs >= 10000000
                        ? round($amount/1000000, 1)
                        : round($amount/1000000, 2)
                    );

            case 'g':
            case 'G':
                return $amount_abs >= 100000000000
                    ? round($amount/1000000000)
                    : ($amount_abs >= 10000000000
                        ? round($amount/1000000000, 1)
                        : round($amount/1000000000, 2)
                    );

            case 't':
            case 'T':
                return $amount_abs >= 100000000000000
                    ? round($amount/1000000000000)
                    : ($amount_abs >= 10000000000000
                        ? round($amount/1000000000000, 1)
                        : round($amount/1000000000000, 2)
                    );

            default:
                if (isset($order))
                    return $amount_abs >= 100
                        ? round($amount)
                        : ($amount_abs >= 10
                            ? round($amount, 1)
                            : round($amount, 2)
                        );
                elseif ($amount_abs >= 1000000000000)
                    return ($amount_abs >= 100000000000000
                        ? round($amount/1000000000000)
                        : ($amount_abs >= 10000000000000
                            ? round($amount/1000000000000, 1)
                            : round($amount/1000000000000, 2)
                        )
                    ).'T';
                elseif ($amount_abs >= 1000000000)
                    return ($amount_abs >= 100000000000
                        ? round($amount/1000000000)
                        : ($amount_abs >= 10000000000
                            ? round($amount/1000000000, 1)
                            : round($amount/1000000000, 2)
                        )
                    ).'G';
                elseif ($amount_abs >= 1000000)
                    return ($amount_abs >= 100000000
                        ? round($amount/1000000)
                        : ($amount_abs >= 10000000
                            ? round($amount/1000000, 1)
                            : round($amount/1000000, 2)
                        )
                    ).'M';
                elseif ($amount_abs >= 1000)
                    return ($amount_abs >= 100000
                        ? round($amount/1000)
                        : ($amount_abs >= 10000
                            ? round($amount/1000, 1)
                            : round($amount/1000, 2)
                        )
                    ).'K';
                else
                    return $amount_abs >= 100
                        ? round($amount)
                        : ($amount_abs >= 10
                            ? round($amount, 1)
                            : round($amount, 2)
                        );
        }
    }

    /** returns human-readable number of bytes with appropriate suffix (P,T,G,M,K).
     * rounded up to a higher integer
     *
     * @param int $bytes    number of bytes to convert
     * @param string $order an order to use, one of (P,T,G,M,K). if provided, no
     *                      suffix is appended
     * @return string value like '150', '9K', '15M', '374G'
     *
     * @assert(20) == '20'
     * @assert(999) == '999'
     * @assert(1000) == '1000'
     * @assert(1024) == '1K'
     * @assert(1025) == '2K'
     * @assert(2048) == '2K'
     * @assert(1048575) == '1024K'
     * @assert(1048576) == '1M'
     * @assert(1048577) == '2M'
     */
    public static function humanBytes(int $bytes, string $order = null): string
    {
        switch($order) {
            case 'k':
            case 'K':
                return (int)ceil($bytes/1024);

            case 'm':
            case 'M':
                return (int)ceil($bytes/1048576);

            case 'g':
            case 'G':
                return (int)ceil($bytes/1073741824);

            case 't':
            case 'T':
                return (int)ceil($bytes/1099511627776);

            case 'p':
            case 'P':
                return (int)ceil($bytes/1125899906842624);

            default:
                if ($bytes >= 1125899906842624)
                    return (int)ceil($bytes/1125899906842624).'P';
                elseif ($bytes >= 1099511627776)
                    return (int)ceil($bytes/1099511627776).'T';
                elseif ($bytes >= 1073741824)
                    return (int)ceil($bytes/1073741824).'G';
                elseif ($bytes >= 1048576)
                    return (int)ceil($bytes/1048576).'M';
                elseif ($bytes >= 1024)
                    return (int)ceil($bytes/1024).'K';
                else
                    return $bytes;
        }
    }

    /** returns html color value from unsigned int
     *
     * @param int $rgba value like 0xAARRGGBB
     * @return string html color value in the form #RRGGBB or rgba(R,G,B,A)
     */
    public static function intToRgba(int $rgba): string
    {
        $alpha = (0xff000000 & $rgba)>>24;

        if ($alpha == 0xff) {
            return sprintf('#%06x', 0xffffff & $rgba);
        } else {
            $red = (0xff0000 & $rgba)>>16;
            $green = (0x00ff00 & $rgba)>>8;
            $blue = 0x0000ff & $rgba;
            return sprintf('rgba(%u,%u,%u,%s)', $red, $green, $blue, round($alpha/255, 2));
        }
    }

    /** returns the parts from <code>$params</code> joined using the first parameter
     * as separator. if part is an array then calls itself recursively providing
     * this array as parameter. if the glue is an array then uses it as ['prefix',
     * 'separator', 'suffix']
     *
     * @param array $params [separator, part1, part2, ...] where
     *                      separator may be a string or array ['prefix', 'separator', 'suffix']
     *                      partN may be a string or array [separatorN, partN1, partN2, ...]
     * @return string
     *
     * @assert(array()) == null
     * @assert(array(' ', 'First', 'Second')) == 'First Second'
     * @assert(array("\n", 'Street', array(' ', 'Postal', 'City'))) == "Street\nPostal City"
     * @assert(array("\n", 'Street', array(' ', 'Postal', null, 'Country'))) == "Street\nPostal Country"
     * @assert(array(array('[', ', ', ']'), 'First', 'Second')) == "[First, Second]"
     * @assert(array("\n", null, null)) == null
     */
    public static function joinWs(array $params): string
    {
        $elements = [];
        $splitter = '';

        foreach ($params as $k=>$v) {
            if ($k) {
                if (isset($v)) {
                    if (is_scalar($v)) {
                        $elements[] = $v;
                    } elseif (($v = self::joinWs($v)) !== null) {
                        $elements[] = $v;
                    }
                }
            } else {
                $splitter = $v;
            }
        }

        if (is_scalar($splitter)) {
            return $elements ? implode($splitter, $elements) : null;
        } elseif ($elements) {
            return $splitter[0].implode($splitter[1], $elements).$splitter[2];
        } else {
            return null;
        }
    }

    /** normalizes path by removing empty and dot (.) dirs, resolving parent (..) dirs and removing starting slash
     *
     * @param string $path path to normalize (dir names may not contain slashes)
     *
     * @assert('') = ''
     * @assert('/') = ''
     * @assert('/etc/hosts') = 'etc/hosts'
     * @assert('.././/tmp/../home//admin/./.ssh') = 'home/admin/.ssh'
     */
    public static function normalizePath(string $path): string
    {
        $dirs = [];
        foreach (explode('/', $path) as $d) {
            if (strlen($d) and $d != '.') {
                if ($d == '..') {
                    array_pop($dirs);
                } else {
                    $dirs[] = $d;
                }
            }
        }

        return implode('/', $dirs);
    }

    /** returns html color converted to unsigned int
     *
     * @param string $color html standard color format (#RGB, #RRGGBB, #RGBA or #RRGGBBAA)
     * @return int unsigned int representation of 1-byte alpha chanel and 3-bytes color or <i>false</i> if color
     * is not of form #RGB, #RRGGBB, #RGBA or #RRGGBBAA.
     * @throws InvalidArgumentException
     *
     * @assert('#000000') = 0xff000000
     * @assert('#0000ff') = 0xff0000ff
     */
    public static function rgbaToInt(string $color): int
    {
        switch (strlen($color)) {
            case 4:
                $color = "{$color[0]}{$color[1]}{$color[1]}{$color[2]}{$color[2]}{$color[3]}{$color[3]}ff";
                break;
            case 5:
                $color = "{$color[0]}{$color[1]}{$color[1]}{$color[2]}{$color[2]}{$color[3]}{$color[3]}{$color[4]}{$color[4]}";
                break;
            case 7:
                $color .= 'ff';
                break;
            case 9:
                break;
            default:
                throw new InvalidArgumentException("Value $color is not in RGBA format");
        }

        if (sscanf(strtolower($color), '#%02x%02x%02x%02x', $r, $g, $b, $a) == 4) {
            return $a<<24 | $r<<16 | $g<<8 | $b;
        } else {
            throw new InvalidArgumentException("Value $color is not in RGBA format");
        }
    }

    /** sets session cookie ttl and starts the session or regenerates session id
     * if session already open
     *
     * @param int $ttl  new time to live in seconds
     */
    public static function sessionSetTtl(int $ttl)
    {
        session_set_cookie_params($ttl);
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        } else {
            session_regenerate_id(true);
        }
    }

    /** escapes a string to be used in sprintf by doubling the % characters
     *
     * @param string $str   string to escape
     * @return string
     *
     * @assert('simple') == 'simple'
     * @assert('line with % sign') == 'line with %% sign'
     */
    public static function sprintfEscape(string $str): string
    {
        return str_replace('%', '%%', $str);
    }

    /** whether the byte code corresponds to a valid UTF-8 leading byte
     *
     * @param int $char
     * @return int|null|false value indicating number of bytes for the Unicode character,
     * <i>0</i> if trailing byte, <i>null</i> if US-ASCII byte,
     * <i>false</i> if incorrect UTF-8 byte (0xC0, 0xC1, 0xF5 .. 0xFF)
     * @see http://tools.ietf.org/html/rfc3629
     *
     * @assert(0x29) === null
     * @assert(0xE2) == 3
     * @assert(0x89) == 0
     * @assert(0xA2) == 0
     * @assert(0xCE) == 2
     * @assert(0xF0) == 4
     * @assert(0xC0) === false
     * @assert(0xC1) === false
     * @assert(0xF5) === false
     * @assert(0xFF) === false
     */
    public static function utf8Leading(string $char)
    {
        if (($char & 0b10000000) == 0b00000000) {       // ascii byte
            return null;
        } elseif (($char & 0b11000000) == 0b10000000) { // trailing byte
            return 0;
        } elseif ($char == 0xC0 or $char == 0xC1 or $char >= 0xF5) { // incorrect
            return false;
        } elseif (($char & 0b11100000) == 0b11000000) { // leading + 1 byte
            return 2;
        } elseif (($char & 0b11110000) == 0b11100000) { // leading + 2 bytes
            return 3;
        } elseif (($char & 0b11111000) == 0b11110000) { // leading + 3 bytes
            return 4;
        } else {                                        // incorrect
            return false;
        }
    }

    /** whether the byte code corresponds to a utf-8 continuation byte
     *
     * @param int $char
     * @return bool
     *
     * @assert(0xA2) === true
     * @assert(0xE2) === false
     * @assert(0x29) === false
     */
    public static function utf8Trailing(string $char): bool
    {
        return ($char & 0b11000000) == 0b10000000;
    }

    /** enhance vsprintf semantics to be able to use named args for argument swapping. so instead of calling:
     *  <pre>vsprintf(
     *      'select * from %1$s where %2$s = %3$u',
     *      ['users', 'user_id', 15]
     *  )</pre>
     * we call:
     *  <pre>vsprintfArgs(
     *      'select * from %tbl$s where %id$s = %value$u',
     *      ['tbl'=&gt;'users', 'id'=&gt;'user_id', 'value'=&gt;15]
     *  )</pre>
     *
     * @param string $format    format string, like 'select * from %tbl$s where %id$s = %value$u'
     * @param array $args       named args array, like ['tbl'=&gt;'users', 'id'=&gt;'user_id', 'value'=&gt;15]
     * @return string formatted string, like select * from users where user_id = 15
     *
     * @assert('select * from %tbl$s where %id$s = %value$u', ['tbl'=>'users', 'id'=>'user_id', 'value'=>15]) == 'select * from users where user_id = 15'
     * @assert('select * from %1$s where %2$s = %3$u', ['users', 'user_id', 15]) == 'select * from users where user_id = 15'
     * @assert('select * from %s where %s = %u', ['users', 'user_id', 15]) == 'select * from users where user_id = 15'
     * @assert('aaa=%aaa$s, aa=%aa$s, a=%a$s', ['a'=>'A', 'aa'=>'AA', 'aaa'=>'AAA']) == 'aaa=AAA, aa=AA, a=A'
     */
    public static function vsprintfArgs(string $format, array $args): string
    {
        $from = [];
        $to = [];
        $i = 0;

        foreach ($args as $k=>$v) {
            if (! is_int($k)) {
                $from[] = "%$k$";
                $to[] = '%'.++$i.'$';
            }
        }

        return vsprintf(str_replace($from, $to, $format), $args);
    }
}
