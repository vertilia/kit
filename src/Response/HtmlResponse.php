<?php

/**
 * HTML template-based response handling
 *
 * @author stas trefilov
 */

namespace Vertilia\Response;

use Vertilia\Ui\Html;

class HtmlResponse extends TemplateResponse
{
    /** @var Html */
    protected $html;

    /** @var array like ['Location: http://www.example.com/', ...] */
    protected $headers_pool = [];

    /** @var array like ['&lt;link rel="stylesheet" href="://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">', ...] */
    protected $head_pool = [];

    /** @var array like ['&lt;h1>Hello world!&lt;/h1>', ...] */
    protected $body_pool = [];

    /** @var array like ['&lt;script src="//backbonejs.org/backbone-min.js">&lt;/script>', ...] */
    protected $scripts_pool = [];

    /** @var array like ['console.log("Loading app...").', ...] */
    protected $onload_pool = [];

    /** @var array ['&lt;script>ga("send","pageview")&lt;/script>', ...] */
    protected $finalize_pool = [];

    /** @var string like "fr" */
    protected $lang;

    /** @var string */
    protected $title;

    /**
     * @param string $template
     * @param Html $html
     * @param string $lang
     */
    function __construct(string $template, Html $html, string $lang = null)
    {
        $this->html = $html;
        $this->lang = $lang;
        parent::__construct($template);
    }

    /**
     * @param string $content
     * @return string
     */
    protected function wrapContent(string $content): string
    {
        ob_start();

        // set HTTP headers
        foreach ($this->headers_pool as $header) {
            header($header);
        }

        // output DOCTYPE
        echo "<!DOCTYPE html>\n";

        // page lang attribute
        if (isset($this->lang)) {
            echo '<html lang="'.$this->html->encodeAttr($this->lang).'">', "\n";
        }

        // add page title to head_pool
        if (isset($this->title)) {
            $this->head_pool[__METHOD__.':title'] = "<title>".$this->html->encode($this->title)."</title>";
        }

        // output HEAD
        if ($this->head_pool) {
            echo "<head>\n", implode("\n", $this->head_pool), "\n";
        }

        // output BODY
        if ($this->body_pool) {
            $first = reset($this->body_pool);
            if (strncasecmp($first, '<body', 5) != 0) {
                echo "<body>\n";
            }
            echo implode("\n", $this->body_pool), "\n";
        }

        // render external content
        echo "$content\n";

        // output scripts_pool at the bottom of the page
        if ($this->scripts_pool) {
            echo implode("\n", $this->scripts_pool), "\n";
        }

        // output onload_pool after the scripts
        if ($this->onload_pool) {
            echo '<script>$(function(){', implode("\n", $this->onload_pool), "});</script>\n";
        }

        // last finally_pool
        if ($this->finalize_pool) {
            echo implode("\n", $this->finalize_pool), "\n";
        }

        return ob_get_clean();
    }
}
