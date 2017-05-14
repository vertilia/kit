<?php

/**
 * Template-based response handling
 *
 * @author stas trefilov
 */

namespace Vertilia\Response;

use UnexpectedValueException;

class TemplateResponse implements Renderable
{
    /** @var string */
    protected $template;

    /**
     * @param string $template
     */
    function __construct(string $template = null)
    {
        $this->template = $template;
    }

    /**
     * @param string $content
     * @return string
     */
    protected function wrapContent(string $content): string
    {
        return $content;
    }

    /**
     * @throws UnexpectedValueException
     */
    function render()
    {
        if (isset($this->template)) {
            ob_start();
            include($this->template);
            echo $this->wrapContent(ob_get_clean());
        } else {
            throw new UnexpectedValueException('Template not set');
        }
    }
}
