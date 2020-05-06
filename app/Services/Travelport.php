<?php

namespace App\Services;

use Exception;

/**
 * Class Travelport
 * @package App\Services
 */
class Travelport extends \FilippoToso\Travelport\Travelport
{
    /**
     * @param $request
     * @return mixed
     * @throws Exception
     */
    public function execute($request)
    {
        if (is_null($request->getTargetBranch())) {
            $request->setTargetBranch($this->targetBranch);
        }

        $service = $this->getService($request);

        if ($this->logger) {

            try {
                $result = $service->__soapCall('service', [$request]);
                $this->logger->setTransactionId($result->getTransactionId());
                $this->logger->log('request', $service, $request, $service->__getLastRequest());
                $this->logger->log('response', $service, $request, $service->__getLastResponse());
                return $result;
            } catch (Exception $travelportException) {
                $this->logger->log('er1', $service, $request, $service->__getLastRequest());
                $this->logger->log('er2', $service, $request, $service->__getLastResponse());
                throw $travelportException;
            }
        }

        return $service->__soapCall('service', [$request]);
    }

}
