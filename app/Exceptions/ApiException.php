<?php

namespace App\Exceptions;

class ApiException extends \Exception
{
    const DEFAULT_ERROR = 1000;
    const ERROR_INVALID_ID = 101;

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
     * @param $id
     * @return ApiException
     */
    public static function getInstanceInvalidId($id): self
    {
        return new self('Invalid id: ' . $id, self::ERROR_INVALID_ID);
    }

    /**
     * @param $massage
     * @return ApiException
     */
    public static function getInstanceValidate($massage)
    {
        return new self('Validation error: ' . $massage);
    }

    public function getResponseArray()
    {
        return [
            'code' => $this->getCode(),
            'message' => $this->getMessage()
        ];
    }

}