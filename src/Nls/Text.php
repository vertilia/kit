<?php

/**
 * Handling translations and locales
 *
 * This class is intended to eliminate the dependency on gettext library since
 * unstable in web environments.
 * The existing .PO catalogue is transformed by a po2php tool to a .PHP file
 * that may be stored by opcode cache. Two variables are defined in the file:
 * $PLURALFORMS and $TRANSLATIONS.
 * $PLURALFORMS is a 'plural' attribute of Plural-Forms header from .PO file
 * with 'n' replaced by '$n' to facilitate evaluation.
 * $TRANSLATIONS is a hash array of the form crc32=>TranslatedString, where
 * crc32 is a CRC32 hash of the source string from .PO file and
 * TranslatedString is a corresponding translated string.
 * When class methods are called with source message as parameter it is first
 * translated into CRC32 form and then looked up in $TRANSLATIONS array.
 *
 * Plural forms and contexts are maintained via corresponding n* and p*
 * methods.
 *
 * Domains are not implemented, just use another Text object by domain.
 *
 * Class methods replace main gettext functions.
 *
 * @author stas trefilov
 */

namespace Vertilia\Nls;

class Text
{
    /** @var Translation auto-generated object with translations from a corresponding .po file */
    protected $source;

    /**
     * @param Translation $translation
     */
    public function __construct(Translation $translation)
    {
        $this->source = $translation;
    }

    /** fetches an existing string from $translations by its hash and
     * message name (in case of collision)
     * @param int $hash         existing hash in $domainTranslations[$domain]
     * @param string $message   the original message used to produce the hash
     * @return string
     */
    protected function fetch(int $hash, string $message): string
    {
        return is_array($this->source->translations[$hash])
            ? (isset($this->source->translations[$hash][$message])
                ? $this->source->translations[$hash][$message]
                : $this->source->translations[$hash][$hash]
            )
            : $this->source->translations[$hash];
    }

    /**
     * @param string $message
     * @return string
     */
    public function _(string $message): string
    {
        $crc = crc32($message);

        return (isset($this->source->translations[$crc]))
            ? $this->fetch($crc, $message)
            : $message;
    }

    /**
     * @param string $context
     * @param string $message
     * @return string
     */
    public function pget(string $context, string $message): string
    {
        $crc = crc32("$message\f$context");

        return (isset($this->source->translations[$crc]))
            ? $this->fetch($crc, $message)
            : $message;
    }

    /**
     * @param string $message1
     * @param string $message2
     * @param int $count
     * @return string
     */
    public function nget(string $message1, string $message2, int $count): string
    {
        $num = $this->source->pluralForm($count);
        $idn = "$message1\f$message2\f".(int)$num;
        $crc = crc32($idn);

        return (isset($this->source->translations[$crc]))
            ? $this->fetch($crc, $idn)
            : ($count == 1 ? $message1 : $message2);
    }

    /**
     * @param string $context
     * @param string $message1
     * @param string $message2
     * @param int $count
     * @return string
     */
    public function pnget(string $context, string $message1, string $message2, int $count): string
    {
        $num = $this->source->pluralForm($count);
        $idn = "$message1\f$context\f$message2\f".(int)$num;
        $crc = crc32($idn);

        return (isset($this->source->translations[$crc]))
            ? $this->fetch($crc, $idn)
            : ($count == 1 ? $message1 : $message2);
    }

    /**
     * @param string $message
     * @return string
     */
    public function noop(string $message): string
    {
        return $message;
    }
}
