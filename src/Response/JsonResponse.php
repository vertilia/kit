<?php

/**
 * JSON response handling
 *
 * @author stas trefilov
 */

namespace Vertilia\Response;

class JsonResponse implements Renderable
{
    const RES_RESULT = 'result';
    const RES_ERROR = 'error';
    const RES_ERR_MSG = 'err_msg';

    /** @var array */
    protected $result;

    /**
     * @param mixed $msg
     * @param int $status
     */
    public function setError($msg, int $status = 400)
    {
        http_response_code($status);

        if (!is_array($this->result)) {
            $this->result = isset($this->result)
                ? [self::RES_RESULT=>$this->result]
                : [];
        }

        $this->result[self::RES_ERROR] = true;
        $this->result[self::RES_ERR_MSG] = $msg;
    }

    /**
     * outputs response in json format with proper http header. on empty
     * response provides 204 http code
     */
    public function render()
    {
        if (isset($this->result)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(204);
        }
    }
}
