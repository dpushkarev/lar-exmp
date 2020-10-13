<?php

namespace App\Exceptions;

class PaymentException extends \Exception
{
    const DEFAULT_ERROR = 1000;

    protected $payment = null;

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
     * @return PaymentException
     */
    public static function getInstance($message, $code = null): PaymentException
    {
        $exception =  new self('Payment error: ' . $message, $code ?: self::DEFAULT_ERROR);

        return $exception;
    }

    public function setPayment($payment)
    {
        $this->payment = $payment;
    }

    public function getPayment()
    {
        return $this->payment;
    }


}