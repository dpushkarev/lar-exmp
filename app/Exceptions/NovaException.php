<?php

namespace App\Exceptions;

class NovaException extends \Exception
{
    const DEFAULT_ERROR = 500;

    /**
     * ApiException constructor.
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
     * @return ApiException
     */
    public static function getInstance($message, $code = null): self
    {
        return new self($message, $code ?? self::DEFAULT_ERROR);
    }

}