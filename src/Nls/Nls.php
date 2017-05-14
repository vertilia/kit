<?php

/**
 * NLS parameters management
 *
 * @author stas trefilov
 */

namespace Vertilia\Nls;

use UnexpectedValueException;

class Nls
{
    public $name;
    public $lang;
    public $charset;
    public $locales;
    public $decimal_point;
    public $thousands_sep;
    public $mon_decimal_point;
    public $mon_thousands_sep;
    public $int_curr_symbol;
    public $currency_symbol;
    public $mon_fmt;
    public $date_dt;
    public $date_full_dt;
    public $datetime_dt;
    public $datetimesec_dt;
    public $datetimesec_fmt;
    public $daterev_fmt;
    public $daterev_re;
    public $datemon_fmt;
    public $datemon_short_fmt;
    public $months;
    public $months_short;
    public $wdays;
    public $wdays_short;
    public $list_delim;
    public $list_delim_html;

    /**
     * @param string $lang
     */
    function __construct(string $lang)
    {
        switch ($lang) {
            case 'fr':
                $this->name = 'Français';
                $this->lang = 'fr';
                $this->charset = 'UTF-8';
                $this->locales = ['fr_FR', 'French_France', 'fr'];
                $this->decimal_point = ',';
                $this->thousands_sep = ' ';
                $this->mon_decimal_point = ',';
                $this->mon_thousands_sep = ' ';
                $this->int_curr_symbol = 'EUR';
                $this->currency_symbol = '€';
                $this->mon_fmt = '%s %s';
                $this->date_dt = 'd/m/y';
                $this->date_full_dt = 'd/m/Y';
                $this->datetime_dt = 'd/m/y H:i';
                $this->datetimesec_dt = 'd/m/y H:i:s';
                $this->datetimesec_fmt = '%u/%u/%u %2u:%2u:%2u';
                $this->daterev_fmt = '%3$u-%2$u-%1$u';
                $this->daterev_re = '$3-$2-$1';
                $this->datemon_fmt = '%s %u';
                $this->datemon_short_fmt = "%s '%u";
                $this->months = [1=>'Janvier', 'Février', 'Mars',
                    'Avril', 'Mai', 'Juin',
                    'Juillet', 'Août', 'Septembre',
                    'Octobre', 'Novembre', 'Décembre',
                ];
                $this->months_short = [1=>'janv.', 'févr.', 'mars',
                    'avr.', 'mai', 'juin',
                    'juil.', 'août', 'sept.',
                    'oct.', 'nov.', 'déc.',
                ];
                $this->wdays = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
                $this->wdays_short = ['Di', 'Lu', 'Ma', 'Me', 'Je', 'Ve', 'Sa'];
                $this->list_delim = ' ;';
                $this->list_delim_html = '&nbsp;;';
                break;

            case 'ru':
                $this->name = 'Русский';
                $this->lang = 'ru';
                $this->charset = 'UTF-8';
                $this->locales = ['ru_RU', 'Russian_Russia', 'ru'];
                $this->decimal_point = ',';
                $this->thousands_sep = ' ';
                $this->mon_decimal_point = ',';
                $this->mon_thousands_sep = ' ';
                $this->int_curr_symbol = 'RUB';
                $this->currency_symbol = '₽';
                $this->mon_fmt = '%s %s';
                $this->date_dt = 'd.m.y';
                $this->date_full_dt = 'd.m.Y';
                $this->datetime_dt = 'd.m.y H:i';
                $this->datetimesec_dt = 'd.m.y H:i:s';
                $this->datetimesec_fmt = '%u.%u.%u %2u:%2u:%2u';
                $this->daterev_fmt = '%3$u-%2$u-%1$u';
                $this->daterev_re = '$3-$2-$1';
                $this->datemon_fmt = '%s %u';
                $this->datemon_short_fmt = "%s '%u";
                $this->months = [1=>'Январь', 'Февраль', 'Март',
                    'Апрель', 'Май', 'Июнь',
                    'Июль', 'Август', 'Сентябрь',
                    'Октябрь', 'Ноябрь', 'Декабрь',
                ];
                $this->months_short = [1=>'янв.', 'февр.', 'март',
                    'апр.', 'май', 'июнь',
                    'июль', 'авг.', 'сент.',
                    'окт.', 'нояб.', 'дек.',
                ];
                $this->wdays = ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'];
                $this->wdays_short = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
                $this->list_delim = ',';
                $this->list_delim_html = ',';
                break;

            default:
                $this->name = 'English';
                $this->lang = 'en';
                $this->charset = 'UTF-8';
                $this->locales = ['en_US', 'English_United States', 'en'];
                $this->decimal_point = '.';
                $this->thousands_sep = ',';
                $this->mon_decimal_point = '.';
                $this->mon_thousands_sep = ',';
                $this->int_curr_symbol = 'USD';
                $this->currency_symbol = '$';
                $this->mon_fmt = '%2$s%1$s';
                $this->date_dt = 'm/d/y';
                $this->date_full_dt = 'm/d/Y';
                $this->datetime_dt = 'm/d/y H:i';
                $this->datetimesec_dt = 'm/d/y H:i:s';
                $this->datetimesec_fmt = '%u/%u/%u %2u:%2u:%2u';
                $this->daterev_fmt = '%3$u-%1$u-%2$u';
                $this->daterev_re = '$3-$1-$2';
                $this->datemon_fmt = '%s %u';
                $this->datemon_short_fmt = "%s '%u";
                $this->months = [1=>'January', 'February', 'March',
                    'April', 'May', 'June',
                    'July', 'August', 'September',
                    'October', 'November', 'December',
                ];
                $this->months_short = [1=>'Jan', 'Feb', 'Mar',
                    'Apr', 'May', 'Jun',
                    'Jul', 'Aug', 'Sep',
                    'Oct', 'Nov', 'Dec',
                ];
                $this->wdays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                $this->wdays_short = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
                $this->list_delim = ',';
                $this->list_delim_html = ',';
        }
    }

    // convert values from internal form to NLS representation

    /** value with thousands/decimal separators and 2 decimal places.
     * Before conversion value is divided by 100. Spaces converted to &nbsp;
     *
     * @param int $cts          value to convert
     * @param bool $show_cents  whether to show the decimal part. if <i>null</i>
     *  passed use compact mode (decimals displayed if exist, up to 2)
     * @return string
     */
    public function asCents(int $cts, bool $show_cents = null): string
    {
        if (isset($show_cents)) {
            // 2 or 0 decimal places
            $dec = $show_cents ? 2 : 0;
        } else {
            // minimum decimal places
            $dec = $cts % 100 ? ($cts % 10 ? 2 : 1) : 0;
        }

        return number_format(
            $cts / 100, $dec, $this->mon_decimal_point, $this->mon_thousands_sep
        );
    }

    /** date representation in current Nls format.
     *
     * @param string $dt    YYYY-MM-DD representation of a date (YYYY-MM-DD HH:MM:SS
     *  if $datetime set)
     * @param bool $datetime
     * @return string
     * @throws UnexpectedValueException
     */
    public function asDate(string $dt, bool $datetime = null): string
    {
        if ($tm = strtotime($dt)) {
            return date($datetime ? $this->datetime_dt : $this->date_dt, $tm);
        } else {
            throw new UnexpectedValueException();
        }
    }

    /** date representation as YYYY-MM-DD
     *
     * @param string $dt    YYYY-MM-DD representation of a date (YYYY-MM-DD HH:MM:SS
     *  if $datetime set)
     * @param bool $datetime
     * @return string
     */
    public function asDateRfc(string $dt, bool $datetime = null): string
    {
        return $datetime ? str_replace(' ', 'T', $dt) : substr($dt, 0, 10);
    }

    /** integer value using specified thousands separator or Nls format if empty
     *
     * @param int $i        value to convert
     * @param string $sep   thousands separator
     * @return string
     */
    public function asInt(int $i, string $sep = null): string
    {
        if (!isset($sep)) {
            $sep = $this->thousands_sep;
        }

        return number_format($i, 0, '', $sep);
    }

    /** month / year representation in current Nls format, like 'January 2015' for '2015-01-01'.
     *
     * @param string $date  YYYY-MM-DD or YYYY-MM representation of a date
     * @param bool $short   whether to use short "Jan'15" or normal "January 2015" format
     * @return string Month representation of a date
     * @throws UnexpectedValueException
     */
    public function asMonth(string $date, bool $short = false): string
    {
        if (preg_match('/^(\d{4})-(\d{2})\b/', $date, $m)
            and isset($this->months[(int) $m[2]])
        ) {
            return $short
                ? sprintf($this->datemon_short_fmt, $this->months_short[(int) $m[2]], $m[1] % 100)
                : sprintf($this->datemon_fmt, $this->months[(int) $m[2]], $m[1]);
        } else {
            throw new UnexpectedValueException();
        }
    }

    /** returns nls-formatted numeric value
     *
     * @param float $value
     * @return string
     */
    public function asNumber(float $value): string
    {
        $v = round($value, 2);

        return number_format($value, max(0, strlen(strrchr($v, '.')) - 1), $this->decimal_point, $this->thousands_sep);
    }

    // convert values from NLS representation to internal form

    /** converts date from nls representation to internal format 2012-12-31 23:59:59
     *
     * @param string $value     the value to convert
     * @param bool $datetime    whether to include time
     * @return string date string
     * @throws UnexpectedValueException
     */
    public function toDate(string $value, bool $datetime = false): string
    {
        $d1 = $d2 = $d3 = $h = $m = $s = null;
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})(?: (\d{2}):(\d{2}):(\d{2}))?/', $value, $m)) {
            if (isset($m[6])) {
                list(, $year, $month, $day, $h, $m, $s) = $m;
            } else {
                list(, $year, $month, $day) = $m;
            }
        } else {
            sscanf($value, $this->datetimesec_fmt, $d1, $d2, $d3, $h, $m, $s);
            list($year, $month, $day) = explode('-', sprintf($this->daterev_fmt, $d1, $d2, $d3));
        }

        if (empty($year)) {
            $year = date('Y');
        } elseif ($year < 50) {
            $year += 2000;
        } elseif ($year < 100) {
            $year += 1900;
        }

        if (checkdate($month, $day, $year)) {
            return $datetime
                ? sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $h, $m, $s)
                : sprintf('%04d-%02d-%02d', $year, $month, $day);
        } else {
            throw new UnexpectedValueException();
        }
    }

    // misc NLS-related string formattings

    /** simplify line by only keeping lowercased alphanumeric symbols and replacing all the rest with dashes,
     * then coalescing repeated dashes and removing trailing dashes.
     * ex: 'Very Common Name, Inc...' becomes 'very-common-name-inc'
     *
     * @param string $line
     * @return string
     *
     * @assert('simple') == 'simple'
     * @assert('simple string') == 'simple-string'
     * @assert('Very Common Name, Inc...') == 'very-common-name-inc'
     */
    public static function simplifyLine(string $line): string
    {
        return trim(preg_replace(
            '/\\W+/',
            '-',
            mb_strtolower($line, $this->charset)
        ), '-');
    }

    /** trims string to the specified length adding suffix
     *
     * @param string $str       string to trim
     * @param int $len          maximal trimmed string lenth
     * @param string $suffix    suffix to add
     * @return string           trimmed string
     *
     * @assert('line') == 'line'
     * @assert('longer line', 8) == 'longe...'
     * @assert('длинная строка', 10, '.') == 'длинная с.'
     */
    public static function trim(string $str, int $len = 0, string $suffix = '...'): string
    {
        return ($len && mb_strlen($str, $this->charset) > $len)
            ? mb_substr($str, 0, $len - max(0, mb_strlen($suffix, $this->charset)), $this->charset).$suffix
            : $str;
    }

    /** trims string to the specified length by word boundary adding suffix
     *
     * @param string $str       string to trim
     * @param int $len          maximal trimmed string lenth
     * @param string $suffix    suffix to add
     * @return string           trimmed string
     *
     * @assert('much longer line', 15) == 'much longer...'
     * @assert('гораздо более длинная строка', 23) == 'гораздо более...'
     */
    public static function trimWord(string $str, int $len = 100, string $suffix = '...'): string
    {
        return mb_substr(
            $str,
            0,
            mb_strlen(
                mb_strrchr(
                    mb_substr($str, 0, $len - mb_strlen($suffix, $this->charset) + 1, $this->charset),
                    ' ',
                    true,
                    $this->charset
                ),
                $this->charset
            ),
            $this->charset
        ).$suffix;
    }
}
