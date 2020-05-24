<?php

namespace App\Exceptions;

class TravelPortException extends \Exception
{
    const DEFAULT_ERROR = 1000;

    /**
     * @inheritdoc
     */
    private function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return ApiException
     */
    public static function getInstance($message, $code = null): TravelPortException
    {
        return new self('TravelPort error: ' . $message, $code ?? self::DEFAULT_ERROR);
    }

}