<?php

/**
 * Auto-generated translations from .po files extend this class.
 *
 * @author stas trefilov
 */

namespace Vertilia\Nls;

class Translation
{
    public $translations = [];

    public function pluralForm(int $n) : int
    {
        return (int)($n > 1);
    }
}
