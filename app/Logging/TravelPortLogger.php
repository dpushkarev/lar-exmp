<?php


namespace App\Logging;

use FilippoToso\Travelport\Air\AirPriceReq;
use FilippoToso\Travelport\Air\AirPriceRsp;
use FilippoToso\Travelport\Air\LowFareSearchReq;
use FilippoToso\Travelport\Air\LowFareSearchRsp;
use \FilippoToso\Travelport\TravelportLogger as BaseTravelPortLogger;

class TravelPortLogger implements BaseTravelPortLogger
{
    const ALIASES = [
        LowFareSearchReq::class => 'LFS-req',
        LowFareSearchRsp::class => 'LFS-rsp',
        AirPriceReq::class => 'AP-req',
        AirPriceRsp::class => 'AP-rsp',
    ];

    const DIR = '/logs/tp';

    private $transactionId;

    /**
     * @param $class
     * @param $service
     * @param $request
     * @param $content
     */
    public function log($class, $service, $request, $content)
    {
        $fileName = static::getPath() . '/' . static::getFileLogName($class, $this->transactionId);
        file_put_contents($fileName, $content);
    }

    static protected function getPath()
    {
        return storage_path() . static::DIR;
    }

    protected static function getFileLogName($class, $transactionId)
    {
        return sprintf("%s-%s.xml", static::ALIASES[$class] ?? getClassName($class), $transactionId);
    }

    public function setTransactionId($transactionId): void
    {
        $this->transactionId = $transactionId;
    }

    /**
     * @param $class
     * @param $transactionId
     * @return false|string|null
     */
    public static function getLog($class, $transactionId)
    {
        $fileName = static::getPath() . '/' . static::getFileLogName($class, $transactionId);

        return file_exists($fileName) ? file_get_contents($fileName) : null;
    }

}