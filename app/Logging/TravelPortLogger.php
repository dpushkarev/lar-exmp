<?php


namespace App\Logging;

use App\Exceptions\TravelPortLoggerException;
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

    const XML_TYPE = 'xml';
    const OBJECT_TYPE = 'obj';

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
        $fileName = static::getPath(static::XML_TYPE) . '/' . static::getFileLogName($class, $this->transactionId, static::XML_TYPE);
        file_put_contents($fileName, $content);
    }

    protected static function getPath($type)
    {
        return storage_path() . static::DIR . '/' . $type;
    }

    protected static function getFileLogName($class, $transactionId, $type)
    {
        return sprintf("%s-%s.%s", static::ALIASES[$class] ?? getClassName($class), $transactionId, $type);
    }

    public function setTransactionId($transactionId): void
    {
        $this->transactionId = $transactionId;
    }

    /**
     * @param $class
     * @param $transactionId
     * @param $type
     * @return false|string
     * @throws TravelPortLoggerException
     */
    public function getLog($class, $transactionId, $type)
    {
        $fileName = static::getPath($type) . '/' . static::getFileLogName($class, $transactionId, $type);

        if(!file_exists($fileName)) {
            throw TravelPortLoggerException::getNonexistentFile();
        }

        return file_get_contents($fileName);
    }

    public function saveSerializedObject($class, $content)
    {
        $fileName = static::getPath(static::OBJECT_TYPE) . '/' . static::getFileLogName($class, $this->transactionId, static::OBJECT_TYPE);
        file_put_contents($fileName, $content);
    }

}