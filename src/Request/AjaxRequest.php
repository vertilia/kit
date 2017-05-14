<?php

/**
 * AJAX HTTP requests handling
 *
 * @author stas trefilov
 */

namespace Vertilia\Request;

class AjaxRequest extends HttpRequest
{
    public function __construct(string $method = null, string $uri = null, string $content_type = null, array $request = [])
    {
        parent::__construct($method, $uri, $request);

        if (!in_array($this->method, [self::METHOD_GET, self::METHOD_POST])) {
            // need to decode request args based on content type
            if (isset($content_type)) {
                list($type) = explode(';', $content_type, 2);
                switch (trim($type)) {
                    case 'application/x-www-form-urlencoded':
                        parse_str(file_get_contents('php://input'), $this->args);
                        break;
                    case 'application/json':
                        $this->args += json_decode(file_get_contents('php://input'), true) ?: [];
                        break;
                }
            }
        }
    }
}
