<?php

namespace App\Exceptions;

class ApiException extends \Exception
{
    const DEFAULT_ERROR = 1000;
    const VALIDATION_ERROR = 422;
    const ERROR_INVALID_CODE = 101;

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
        return new self('Api error: ' . $message, $code ?? self::DEFAULT_ERROR);
    }

    /**
     * @param $code
     * @return ApiException
     */
    public static function getInstanceInvalidCode($code): self
    {
        return new self('Invalid code: ' . $code, self::ERROR_INVALID_CODE);
    }

    /**
     * @param $massage
     * @param int $code
     * @return ApiException
     */
    public static function getInstanceValidate($massage, $code = self::VALIDATION_ERROR)
    {
        return new self('Validation error: ' . $massage, $code);
    }

    public function getResponseArray()
    {
        return [
            'code' => $this->getCode(),
            'message' => $this->getMessage()
        ];
    }

}