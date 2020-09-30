<?php


namespace App\Logging;

use App\Exceptions\TravelPortLoggerException;
use FilippoToso\Travelport\Air\AirFareRulesRsp;
use FilippoToso\Travelport\Air\AirPriceReq;
use FilippoToso\Travelport\Air\AirPriceRsp;
use FilippoToso\Travelport\Air\LowFareSearchReq;
use FilippoToso\Travelport\Air\LowFareSearchRsp;
use \FilippoToso\Travelport\TravelportLogger as BaseTravelPortLogger;
use FilippoToso\Travelport\UniversalRecord\AirCreateReservationReq;
use FilippoToso\Travelport\UniversalRecord\AirCreateReservationRsp;
use Illuminate\Support\Facades\File;

class TravelPortLogger implements BaseTravelPortLogger
{
    const ALIASES = [
        LowFareSearchReq::class => 'LFS-req',
        LowFareSearchRsp::class => 'LFS-rsp',
        AirPriceReq::class => 'AP-req',
        AirPriceRsp::class => 'AP-rsp',
        AirCreateReservationReq::class => 'ACR-req',
        AirCreateReservationRsp::class => 'ACR-rsp',
        AirFareRulesRsp::class => 'AFR-rsp'
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
        $fileName = static::getPath(static::XML_TYPE, $this->transactionId, $class) . '/' . static::getFileLogName($class, $this->transactionId, static::XML_TYPE);
        file_put_contents($fileName, $content);
    }

    protected static function getPath($type, $transactionId, $class)
    {
        list($dir1, $dir2) = [substr($transactionId, 0, 2), substr($transactionId, 2, 2)];
        $alias = static::ALIASES[$class] ?? getClassName($class);
        $path = sprintf('%s/%s/%s/%s/%s', storage_path() . static::DIR, $type, $alias, $dir1, $dir2);
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }

        return $path;
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
        $fileName = static::getPath($type, $transactionId, $class) . '/' . static::getFileLogName($class, $transactionId, $type);

        if (!file_exists($fileName)) {
            throw TravelPortLoggerException::getNonexistentFile();
        }

        return file_get_contents($fileName);
    }

    public function saveSerializedObject($class, $content)
    {
        $fileName = static::getPath(static::OBJECT_TYPE, $this->transactionId, $class) . '/' . static::getFileLogName($class, $this->transactionId, static::OBJECT_TYPE);
        file_put_contents($fileName, $content);
    }

}