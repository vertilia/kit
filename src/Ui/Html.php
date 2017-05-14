<?php

/**
 * HTML output functions.
 *
 * Formats HTML elements and returns values that are safe to directly display on an HTML page.
 *
 * @author stas trefilov
 */

namespace Vertilia\Ui;

use Vertilia\Nls\Nls;
use Vertilia\Util\Params;

class Html
{
    const P_CAPTION         = 1;
    const P_CAPTION_ATTR    = 2;
    const P_COLGROUP        = 3;
    const P_TAG             = 4;
    const P_VALUES          = 5;
    const P_TD_ATTR         = 6;
    const P_DATETIME        = 7;
    const P_BLANK           = 8;
    const P_TYPE            = 9;
    const P_ITEMS           = 10;
    const P_DELIM           = 11;
    const P_SUFFIX          = 13;
    const P_WRAP_FMT        = 14;
    const P_HEADER          = 15;
    const P_HEADER_ATTR     = 16;

    /** @var Nls */
    public $nls;

    function __construct(Nls $nls)
    {
        $this->nls = $nls;
    }

    /** returns html tag attributes concatenated from an array
     *
     * @param array $params {src:'myframe.php', title:'My frame', class:'ifr_class'}
     * @return string       attributes of an html tag, like ' src="myframe.php" title="My frame" class="ifr_class"'
     *
     * @assert (['class'=>'ifr_class']) == ' class="ifr_class"'
     * @assert (['class'=>'ifr_class', 'checked'=>true]) == ' class="ifr_class" checked'
     * @assert (['src'=>'myframe.php', 'title'=>'My frame', 'class'=>'ifr_class']) == ' src="myframe.php" title="My frame" class="ifr_class"'
     */
    public function asAttr(array $params): string
    {
        $ret = [];
        foreach ($params as $attr => $value) {
            if (isset($value)) {
                $ret[] = " $attr=\"".$this->encodeAttr($value).'"';
            } else {
                $ret[] = " $attr";
            }
        }

        return $ret ? implode('', $ret) : '';
    }

    /** returns a string with url-encoded parameters
     *
     * @param string $prefix    prefix to use (normally '?')
     * @param array $params     hash of parameters to encode, like {a:'b',c:{d:'e'}}
     * @return string           url-encoded list, like '?a=b&c%5Bd%5D=e'
     *
     * @assert ('?', ['a'=>'b']) == '?a=b'
     * @assert ('?', ['a'=>'b', 'c'=>'d']) == '?a=b&c=d'
     * @assert ('&', ['a'=>'b', 'c'=>'d']) == '&a=b&c=d'
     * @assert ('?', []) == ''
     */
    public function urlArgs(string $prefix, array $params): string
    {
        $args = http_build_query($params);
        return strlen($args) ? "$prefix$args" : '';
    }

    /** translates special chars in the string to html entities.
     *
     * @param string $str   value to convert
     * @return string
     */
    public function encode(string $str): string
    {
        return htmlspecialchars($str, ENT_NOQUOTES, $this->nls->charset);
    }

    /** translates special chars in the string to html entities.
     *
     * @param string $str   value to convert
     * @param int $flags    htmlspecialchars() flags (default: ENT_COMPAT)
     * @return string
     */
    public function encodeAttr(string $str, int $flags = ENT_COMPAT): string
    {
        return htmlspecialchars($str, $flags, $this->nls->charset);
    }

    /** translates special chars in the string to html entities, then converts newlines to &lt;br /&gt;.
     *
     * @param string $str   value to convert
     * @return string
     */
    public function encodeNl(string $str): string
    {
        return nl2br(htmlspecialchars($str, ENT_NOQUOTES, $this->nls->charset));
    }

    /** Html tables */

    /** returns table open tag followed by a CAPTION and COLGROUP constructs. table is styled to provide column widths and aligns via css
     *
     * @param array $params {
     *  P_CAPTION:'My table',
     *  P_CAPTION_ATTR:{id:'table-capture'},
     *  P_COLGROUP:[{width:"80%"},{width:"20%",align:"right"}],
     *  table tag attributes
     * }
     * @return string
     * @assert (array(Html::P_COLGROUP)) == ''
     */
    public function tableStart(array $params = []): string
    {
        static $cnt = 0;

        $id = Params::extract($params, 'id');
        $caption_attr = Params::extract($params, self::P_CAPTION_ATTR);
        if (is_array($caption_attr)) {
            $caption_attr = $this->asAttr($caption_attr);
        }
        if ($caption = Params::extract($params, self::P_CAPTION)) {
            $caption = "<caption$caption_attr>$caption</caption>";
        }
        $colgroup_html = null;
        $table_style = [];
        if ($colgroup = Params::extract($params, self::P_COLGROUP)) {
            if ($id === null) {
                $id = 'table-implicit-'.++$cnt;
            }
            $k = 0;
            $extra = 0;
            foreach ($colgroup as &$col) {
                $style = [];
                if ($align = Params::extract($col, 'align')) {
                    $style[] = "text-align:$align;";
                }
                if ($width = Params::extract($col, 'width')) {
                    $style[] = "width:$width;";
                }
                if ($style) {
                    $style_html = implode('', $style);
                    $table_style[] =
                        "table#$id th:first-child".str_repeat(' + th', $k)."{{$style_html}}".
                        "table#$id td:first-child".str_repeat(' + td', $k)."{{$style_html}}";
                }
                ++$k;
                if ($col) {
                    ++$extra;
                }
            }
            if ($extra) {
                $colgroup_html = $this->colgroup($colgroup);
            }
        }

        return ($table_style ? '<style>'.implode('', $table_style).'</style>' : null).
            '<table'.$this->asAttr(['id'=>$id] + $params).">$caption$colgroup_html";
    }

    /** returns table closing tag
     *
     * @return string
     */
    public function tableStop(): string
    {
        return '</table>';
    }

    /** returns table columns description implemented with COLGROUP construct
     *
     * @param array $cols   [{width:"80%"},{width:"20%"}]
     * @return string
     */
    public function colgroup(array $cols): string
    {
        return '<colgroup><col'.
            implode('><col', array_map(function ($at) {return $this->asAttr($at);}, $cols)).
            "></colgroup>\n";
    }

    /** returns table row with a set of TD or TH cells
     *
     * @param array $params {
     *  P_VALUES:{'cell1','cell2','cell3'},
     *  P_TD_ATTR:{null,null,' style="text-align:right"'}|null,
     *  P_TAG:'th'|'td'|null,
     *  tr tag attributes
     * }
     * @return string
     */
    public function tr(array $params): string
    {
        $tag = Params::extract($params, self::P_TAG, 'td');
        $td_attr = Params::extract($params, self::P_TD_ATTR, []);
        $values = Params::extract($params, self::P_VALUES, []);
        $res = ['<tr'.$this->asAttr($params).'>'];

        if ($td_attr) {
            foreach ($values as $k => $v) {
                $attr = isset($td_attr[$k]) ? $this->asAttr($td_attr[$k]) : null;
                $res[] = "<$tag$attr>$v</$tag>";
            }
        } else {
            foreach ($values as $v) {
                $res[] = "<$tag>$v</$tag>";
            }
        }

        return implode('', $res)."</tr>\n";
    }



    /** Html forms input elements */

    /**
     * @param array $params {textarea tag attributes}
     * @return string
     * @see http://www.w3.org/TR/html4/interact/forms.html#h-17.7
     */
    public function inputTextarea(array $params): string
    {
        if (isset($params['id']) and empty($params['name'])) {
            $params['name'] = $params['id'];
        }
        $value = Params::extract($params, 'value');
        $attr = $this->asAttr($params);

        return "<textarea$attr>$value</textarea>";
    }

    /** input element(if id is set and name is omitted then name = id)
     *
     * @param array $params {input tag attributes}
     * @return string
     * @see http://www.w3.org/TR/html4/interact/forms.html#h-17.4
     */
    public function input(array $params): string
    {
        if (isset($params['id']) and empty($params['name'])) {
            $params['name'] = $params['id'];
        }

        return '<input'.$this->asAttr($params).'>';
    }

    /**
     * @param array $params {input tag attributes}
     * @return string
     */
    public function inputText(array $params): string
    {
        if (empty($params['type'])) {
            $params['type'] = 'text';
        }

        return $this->input($params);
    }

    /**
     * @param array $params {input tag attributes}
     * @return string
     */
    public function inputInt(array $params): string
    {
        return $this->input($params + ['type'=>'number', 'maxlength'=>10]);
    }

    /**
     * @param array $params {input tag attributes}
     * @return string
     */
    public function inputCents(array $params): string
    {
        if (isset($params['value'])) {
            $params['value'] = $params['value'] / 100;
        }

        return $this->input($params + ['type'=>'number', 'maxlength'=>10]);
    }

    /**
     * @param array $params {P_DATETIME:true, input tag attributes}
     * @return string
     */
    public function inputDate(array $params): string
    {
        $datetime = Params::extract($params, self::P_DATETIME);
        if (!empty($params['value'])) {
            $params['value'] = (empty($params['type'])
                or $params['type'] == 'date'
                or $params['type'] == 'datetime'
                or $params['type'] == 'datetime-local'
            )
                ? $this->nls->asDateRfc($params['value'], $datetime)
                : $this->nls->asDate($params['value'], $datetime);
        }

        return $this->input($params + ['type'=>$datetime ? 'datetime' : 'date', 'maxlength'=>30]);
    }

    /**
     * @param array $params {
     *  P_ITEMS:{a:'Active',i:'Inactive'},
     *  P_BLANK:'First line message',
     *  value:'a',
     *  select tag attributes
     * }
     * @return string
     * @see http://www.w3.org/TR/html4/interact/forms.html#h-17.6
     */
    public function inputSelect(array $params): string
    {
        if (isset($params['id']) and empty($params['name'])) {
            $params['name'] = $params['id'];
        }
        $blank = Params::extract($params, self::P_BLANK, false);
        $value = Params::extract($params, 'value');
        $items = [];

        if ($blank !== false) {
            $items[] = strlen($blank)
                ? ('<option value="">'.$this->encode($blank)."</option>")
                : '<option></option>';
        }

        foreach (Params::extract($params, self::P_ITEMS, []) as $k => $v) {
            $items[] = ($k === $value)
                ? "<option value=\"$k\" selected=\"on\">$v</option>\n"
                : "<option value=\"$k\">$v</option>\n";
        }
        $attr = $this->asAttr($params);
        $items_html = implode('', $items);

        return "<select$attr>\n$items_html</select>";
    }

    /** multiple checkboxes with labels (names are suffixed with *[k] and ids with *_k)
     * calls inputCheckbox()
     *
     * @param array $params {
     *  id:'fld',
     *  name:'field_name',
     *  value:'a,i'|{a:'a',i:'i'},
     *  P_ITEMS:{a:'Active',i:'Inactive'},
     *  P_DELIM:'&lt;br&gt;',
     * }
     * @return string
     */
    public function inputSet(array $params): string
    {
        $id = Params::extract($params, 'id');
        $name = Params::extract($params, 'name');
        if (isset($id) and empty($name)) {
            $name = $id;
        }
        $value = Params::extract($params, 'value');
        $delim = Params::extract($params, self::P_DELIM, '<br>');
        if (!is_array($value)) {
            if (isset($value)) {
                $_ = explode(',', $value);
                $value = array_combine($_, $_);
            } else {
                $value = [];
            }
        }

        $items = [];
        foreach (Params::extract($params, self::P_ITEMS) as $k => $v) {
            $items[] = $this->inputCheckbox([
                'name'=>"{$name}[$k]",
                'checked'=>isset($value[$k]) ? 'on' : null,
                'value'=>$k,
                self::P_HEADER=>$v,
            ] + $params);
        }

        return implode($delim, $items);
    }

    /** returns html radios with labels
     *
     * @param array $params {
     *  id:'fld',
     *  name:'field_name',
     *  value:'a',
     *  P_ITEMS:{a:'Active',i:'Inactive'},
     *  P_DELIM:'&lt;br&gt;',
     *  P_WRAP_FMT:'%s',
     *  P_HEADER_ATTR:{'class':'checkbox'}
     * }
     * @return string
     */
    public function inputRadio(array $params): string
    {
        $id = Params::extract($params, 'id');
        $name = Params::extract($params, 'name');
        if (isset($id) and empty($name)) {
            $name = $id;
        }
        $delim = Params::extract($params, self::P_DELIM, '<br>');
        $fmt = Params::extract($params, self::P_WRAP_FMT);
        $value = Params::extract($params, 'value');
        if ($label_attr = Params::extract($params, self::P_HEADER_ATTR)) {
            $label_attr = $this->asAttr($label_attr);
        }

        $items = [];
        foreach (Params::extract($params, self::P_ITEMS, []) as $k => $v) {
            $item = "<label$label_attr><input".
                $this->asAttr([
                    'type'=>'radio',
                    'name'=>$name,
                    'value'=>$k,
                    'checked'=>($k == $value and ! empty($k)) ? 'on' : null
                ] + $params).
                ">$v</label>";
            $items[] = isset($fmt) ? sprintf($fmt, $item) : $item;
        }

        return implode($delim, $items);
    }

    /** returns html checkbox element
     *
     * @param array $params {
     *  P_HEADER:'string',
     *  P_HEADER_ATTR:{label tag attributes},
     *  P_DELIM:' ',
     *  P_WRAP_FMT:'%s',
     *  input tag attributes
     * }
     * @return string
     */
    public function inputCheckbox(array $params): string
    {
        $attr = array_diff_key($params, [
            self::P_WRAP_FMT=>true,
            self::P_HEADER=>true,
            self::P_HEADER_ATTR=>true,
            self::P_DELIM=>true
        ]);
        $fmt = isset($params[self::P_WRAP_FMT]) ? $params[self::P_WRAP_FMT] : '%s';
        $header = isset($params[self::P_HEADER]) ? $params[self::P_HEADER] : null;
        $header_attr = isset($params[self::P_HEADER_ATTR]) ? $this->asAttr($params[self::P_HEADER_ATTR]) : null;
        $delim = isset($params[self::P_DELIM]) ? $params[self::P_DELIM] : ' ';
        $checkbox = $this->input(['type'=>'checkbox'] + $attr);
        if (isset($header)) {
            $checkbox = "<label$header_attr>$checkbox$delim$header</label>";
        } elseif (isset($header_attr)) {
            $checkbox = "<div$header_attr>$checkbox</div>";
        }

        return sprintf($fmt, $checkbox);
    }
}
