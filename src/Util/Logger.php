<?php

/**
 * Implementation of PSR-3 logger
 *
 * @author stas trefilov
 */

namespace Vertilia\Util;

use Exception;
use InvalidArgumentException;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class Logger extends AbstractLogger
{
    const METHOD = 'method';
    const EXCEPTION = 'exception';
    const STD_MESSAGE = '{'.self::METHOD.'}(): {'.self::EXCEPTION.'}';

    protected function parse(string $message, array $context = []): string
    {
        $from = [];
        $to = [];
        foreach ($context as $key => $value) {
            $from[] = "{{$key}}";
            $to[] = ($key == self::EXCEPTION and $value instanceof Exception) ? $value->getMessage() : (string) $value;
        }
        return str_replace($from, $to, $message);
    }

    public function log(string $level, string $message, array $context = [])
    {
        if (!in_array(
            $level,
            [
                LogLevel::EMERGENCY,
                LogLevel::ALERT,
                LogLevel::CRITICAL,
                LogLevel::ERROR,
                LogLevel::WARNING,
                LogLevel::NOTICE,
                LogLevel::INFO,
                LogLevel::DEBUG,
            ]
        )) {
            throw new InvalidArgumentException("Level $level is not allowed");
        }

        error_log("*** [Level: $level] *** ".$this->parse($message, $context));
    }
}
