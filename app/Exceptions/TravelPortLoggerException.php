<?php

namespace App\Exceptions;

class TravelPortLoggerException extends \Exception
{
    const DEFAULT_ERROR = 1000;

    /**
     * TravelPortLoggerException constructor.
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
     * @return TravelPortLoggerException
     */
    public static function getInstance($message, $code = null): self
    {
        return new self('TravelPortLoggerException error: ' . $message, $code ?? self::DEFAULT_ERROR);
    }

    /**
     * @return TravelPortLoggerException
     */
    public static function getNonexistentFile(): self
    {
        return new self('TravelPortLoggerException error: logfile not found', self::DEFAULT_ERROR);
    }

}