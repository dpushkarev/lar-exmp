<?php


namespace App\Logging;

use \FilippoToso\Travelport\TravelportLogger as BaseTravelPortLogger;

class TravelPortLogger implements BaseTravelPortLogger
{
    private $transactionId;

    public function log($type, $service, $request, $content)
    {
        $fileName = storage_path().'/logs/tp/' . sprintf("%s-%s.xml", substr($type, 0, 3), $this->transactionId);
        file_put_contents($fileName, $content);
    }

    public function setTransactionId($transactionId): void
    {
        $this->transactionId = $transactionId;
    }

}