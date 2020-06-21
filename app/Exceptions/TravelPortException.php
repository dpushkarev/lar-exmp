<?php

namespace App\Exceptions;

class TravelPortException extends \Exception
{
    const DEFAULT_ERROR = 1000;

    private $transactionId = null;

    /**
     * @inheritdoc
     */
    private function __construct(string $message = "", int $code, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @param $message
     * @param null $code
     * @param null $transactionId
     * @return TravelPortException
     */
    public static function getInstance($message, $code = null, $transactionId = null): TravelPortException
    {
        $exception =  new self('TravelPort error: ' . $message, $code ?: self::DEFAULT_ERROR);
        $exception->setTransactionId($transactionId);

        return $exception;
    }

    /**
     * @param $transactionId
     */
    protected function setTransactionId($transactionId): void
    {
        $this->transactionId = $transactionId;
    }

    /**
     * @return string|null
     */
    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

}