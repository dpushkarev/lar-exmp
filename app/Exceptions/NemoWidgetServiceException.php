<?php

namespace App\Exceptions;

class NemoWidgetServiceException extends \Exception
{
    const DEFAULT_ERROR = 1000;

    /**
     * NemoWidgetServiceException constructor.
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    private function __construct(string $message = "", int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @param $message
     * @param null $code
     * @return NemoWidgetServiceException
     */
    public static function getInstance($message, $code = null): self
    {
        return new self('NemoWidgetService error: ' . $message, $code ?? self::DEFAULT_ERROR);
    }

}