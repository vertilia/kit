<?php

/**
 * .po files converter to Vertilia toolkit format
 *
 * script usage:
 * cd vendor/vertilia/kit/src/
 * php Nls/po2class.php Locale/en/vertilia-kit.po VertiliaKitTranslation Vertilia > Locale/en/VertiliaKitTranslation.php
 * php Nls/po2class.php Locale/fr/vertilia-kit.po VertiliaKitTranslation Vertilia > Locale/fr/VertiliaKitTranslation.php
 *
 * @author stas trefilov
 */

if (empty($argv[2])
	or ! file_exists($argv[1])
) {
	die("Usage: ".basename($argv[0])." SOURCE.PO CLASS_NAME [NS_PREFIX]\nWhere:\n  SOURCE.PO - an existing .PO file to convert\n  CLASS_NAME - resulting class name in target namespace\n  NS_PREFIX - namespace prefix for resulting class\n");
}

// where the translations will be stored
$PLURALFORMS = null;
$TRANSLATIONS = [];

// global variables
$mode = null;

$msgctxt = null;
$msgid = null;
$msgid_plural = null;
$msgstr = null;

$last_str = null;

function store_translation($msgctxt, $msgid, $msgid_plural, $msgstr)
{
	global $TRANSLATIONS;

	eval("\$msgid = \"$msgid\";");

	$id = $msgid;

	if (isset($msgctxt)) {
		$id .= "\f$msgctxt";
    }

    if (is_array($msgstr)) {
		eval("\$msgid_plural = \"$msgid_plural\";");
		$id .= "\f$msgid_plural";

		foreach ($msgstr as $n => $str) {
			$idn = "$id\f$n";
			$crc = crc32($idn);
			eval("\$str = \"$str\";");

			if (empty($TRANSLATIONS[$crc])) {
				$TRANSLATIONS[$crc] = $str;
            } elseif (is_array($TRANSLATIONS[$crc])) {
				$TRANSLATIONS[$crc][$idn] = $str;
            } else {
				$TRANSLATIONS[$crc] = [
                    $crc=>$TRANSLATIONS[$crc],
                    $idn=>$str,
                ];
            }
		}
	}
	else
	{
		$crc = crc32($id);
		eval("\$msgstr = \"$msgstr\";");

		if (empty($TRANSLATIONS[$crc])) {
			$TRANSLATIONS[$crc] = $msgstr;
        } elseif (is_array($TRANSLATIONS[$crc])) {
			$TRANSLATIONS[$crc][$id] = $msgstr;
        } else {
			$TRANSLATIONS[$crc] = [
                $crc=>$TRANSLATIONS[$crc],
                $id=>$msgstr,
            ];
        }
	}
}

function dispatch_last_str($mode, $last_str)
{
    global $msgctxt,
        $msgid,
        $msgid_plural,
        $msgstr;

    switch ($mode) {
        case 'msgctxt':
            $msgctxt = $last_str;
            break;
        case 'msgid':
            $msgid = $last_str;
            break;
        case 'msgid_plural':
            $msgid_plural = $last_str;
            break;
        case 'msgstr':
            $msgstr = $last_str;
            break;
        default:
            if (substr($mode, 0, 6) == 'msgstr') { // 'msgstr[N]'
                if (is_array($msgstr)) {
                    $msgstr[] = $last_str;
                } else {
                    $msgstr = array($last_str);
                }
            }
    }
}

foreach (file($argv[1]) as $line)
{
	$line = rtrim($line);

	if (isset($line[0]) and $line[0] == '"') { // continued multiline
		$last_str .= substr($line, 1, -1);
    } else {
        dispatch_last_str($mode, $last_str);

		if (strlen($line) == 0) { // empty line
			if (isset($msgid)) {
				store_translation($msgctxt, $msgid, $msgid_plural, $msgstr);
				$mode = null;
				$msgctxt = null;
				$msgid = null;
				$msgid_plural = null;
				$msgstr = null;
			}
		} elseif ($line[0] != '#'
			and preg_match('/^([^"]+)"(.*)"$/', $line, $m) // keyword " line "
		) {
			// possible keywords in $m[1]: msgctxt, msgid, msgid_plural, msgstr, msgstr[N]

			$mode = rtrim($m[1]);
			$last_str = $m[2];
		}
	}
}

if (isset($msgid)) {
    dispatch_last_str($mode, $last_str);
	store_translation($msgctxt, $msgid, $msgid_plural, $msgstr);
}

if (isset($TRANSLATIONS[0])) {
	if (is_array($TRANSLATIONS[0])) {
		$header = $TRANSLATIONS[0][0];
		unset($TRANSLATIONS[0][0]);
	} else {
		$header = $TRANSLATIONS[0];
		unset($TRANSLATIONS[0]);
	}

    if (preg_match('/^\s*Plural-Forms:.*?plural=([^;]+)/im', $header, $m)) {
		$PLURALFORMS = str_replace('n', '$n', $m[1]);
	}
}

$parts = [];
if ($TRANSLATIONS) {
    $parts[] = '    public $translations = '.var_export($TRANSLATIONS, 1).";\n";
}
if ($PLURALFORMS) {
    if ($PLURALFORMS[0] != '(') {
        $PLURALFORMS = "($PLURALFORMS)";
    }
    $parts[] = '    public function pluralForm(int $n) : int {return (int)'.$PLURALFORMS.";}\n";
}

printf(
<<<'EOt'
<?php

/**
 * This is an auto-generated class, do not edit.
 * Update the corresponding .po file and run it through converter.
 * @see Nls/%s
 */

namespace %s%s;

class %s extends \Vertilia\Nls\Translation
{
%s}

EOt
    ,
    basename($argv[0]),
    isset($argv[3]) ? "$argv[3]\\" : null,
    str_replace(['/', '-'], ['\\', '_'], pathinfo($argv[1], PATHINFO_DIRNAME)),
    $argv[2],
    implode("\n", $parts)
);
