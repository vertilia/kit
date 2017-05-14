<?php

/**
 * HTTP requests handling
 *
 * @author stas trefilov
 */

namespace Vertilia\Request;

use Vertilia\Util\Misc;

class HttpRequest extends Request
{
    const METHOD_DELETE = 'DELETE';
    const METHOD_GET = 'GET';
    const METHOD_HEAD = 'HEAD';
    const METHOD_OPTIONS = 'OPTIONS';
    const METHOD_PATCH = 'PATCH';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_TRACE = 'TRACE';

    /** @var array */
    protected $routes;

    /** @var string */
    public $method;
    /** @var string */
    public $path;
    /** @var string */
    public $controller;

    public function __construct(string $method = null, string $uri = null, array $request = [])
    {
        $this->method = $method;
        if (isset($uri)) {
            $this->path = '/'.Misc::normalizePath(parse_url($uri, PHP_URL_PATH));
        }
        parent::__construct($request);
    }

    /** determines language code from Accept-Language http header (if matches
     * available languages) or set as $default_lang otherwise.
     * <pre>Accept-Language: fr-FR,fr;q=0.8,en-US;q=0.6,en;q=0.4,ru;q=0.2</pre>
     * convert from <code>en-US</code> to <code>en_US</code> form and
     * process comma-separated language groups from left to right. in each
     * group only take part before semicolon. if the part does not match, try
     * to reduce it to first two letters and check again. if does not match
     * move to the next group. if no more groups, return
     * <code>$default_lang</code>.
     *
     * @param string $accept_language Accept-Language http header, ex: 'fr-FR,fr;q=0.8,en-US;q=0.6,en;q=0.4,ru;q=0.2'
     * @param array $languages list of languages that the application understands, ex: ['en', 'fr_FR']
     * @param string $default_lang default language if cannot guess from user agent
     * @return string
     */
    public function getLang(string $accept_language, array $languages, string $default_lang = 'en'): string
    {
        if (isset($accept_language)) {
            // convert from en-US to en_US form and break on groups by , symbol
            foreach (explode(',', strtr($accept_language, '-', '_')) as $group) {
                // in each group only take part before ; symbol (if present)
                list($lang) = explode(';', ltrim($group));
                if (array_search($lang, $languages) !== false) {
                    return $lang;
                }
                // if lang does not match as a whole, try to match the first 2 letters of lang
                $ln = substr($lang, 0, 2);
                if (array_search($ln, $languages) !== false) {
                    return $ln;
                }
            }
        }

        return $default_lang;
    }

    /** parses $routes and sets $this->routes to hold request method, path pattern and
     * corresponding controller.
     *
     * @param array $routes
     *
     * @assert ([
     *  'GET /',
     *  'POST /api/{ver}/contracts'=>'App\\Contracts',
     *  'DELETE /api/{ver}/contracts/{id}'
     * ]) == [
     *  'GET'=>[
     *      '#^/$#'=>null,
     *  ],
     *  'POST'=>[
     *      '#^/api/(?P<ver>[^/]+)/contracts$#'=>'App\\Contracts',
     *  ],
     *  'DELETE'=>[
     *      '#^/api/(?P<ver>[^/]+)/contracts/(?P<id>[^/]+)$#'=>'api\\contracts',
     *  ],
     * ]
     */
    public function setRoutes(array $routes)
    {
        $struct = [];
        foreach ($routes as $k => $route) {
            if (is_string($k)) {
                $controller = $route;
                $route = $k;
            } else {
                $controller = null;
            }

            list($method, $path) = preg_split('/\s+/', "$route ");
            $preg_parts = [];
            $dir_parts = [];
            foreach (explode('/', Misc::normalizePath($path)) as $path_part) {
                $m = [];
                if (substr($path_part, 0, 1) == '{' and preg_match('#^\{([[:alpha:]_]\w*)\}$#', $path_part, $m)) {
                    $preg_parts[] = "(?P<{$m[1]}>[^/]+)";
                } elseif (strlen($path_part)) {
                    $preg_parts[] = preg_quote($path_part, '#');
                    $dir_parts[] = $path_part;
                }
            }
            $struct[$method]['#^/'.implode('/', $preg_parts).'$#'] = isset($controller)
                ? $controller
                : ($dir_parts ? implode('\\', $dir_parts) : null);
        }

        $this->routes = $struct;
    }

    public function parseRoute()
    {
        if (empty($this->routes)
            or ! is_array($this->routes)
            or empty($this->routes[$this->method])
        ) {
            return false;
        }

        foreach ($this->routes[$this->method] as $regex => $controller) {
            $m = null;
            if (preg_match($regex, $this->path, $m)) {
                $this->controller = $controller;
                foreach ($m as $k => $v) {
                    if (is_string($k)) {
                        $this->args[$k] = $v;
                    }
                }
                return true;
            }
        }

        return false;
    }
}
