<?php

namespace Libs\FilippoToso;

use Exception;
use FilippoToso\Travelport\Endpoints;
use FilippoToso\Travelport\Exceptions\InvalidRegionException;

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

    protected function getService($request)
    {
        $serviceUrl = $this->getServiceUrl($request);
        $options = $this->getOptions();

        $class = get_class($request);
        $binding = Bindings::get($class);
        $service = new $binding['service']($options, $binding['wsdl']);
        $service->__setLocation($serviceUrl);

        return $service;
    }

    protected function getServiceUrl($request)
    {
        $validRegions = [Endpoints::REGION_AMERICAS, Endpoints::REGION_APAC, Endpoints::REGION_EMEA];

        if (!in_array($this->region, $validRegions)) {
            throw new InvalidRegionException('Invalid region: ' . $this->region);
        }

        $class = get_class($request);
        $binding = Bindings::get($class);

        // TODO: Implement sharedUprofile support

        if ($this->region == Endpoints::REGION_AMERICAS) {
            return $this->production ? Endpoints::PRODUCTION_AMERICAS . $binding['url'] : Endpoints::PREPRODUCTION_AMERICAS . $binding['url'];
        } elseif ($this->region == Endpoints::REGION_APAC) {
            return $this->production ? Endpoints::PRODUCTION_APAC . $binding['url'] : Endpoints::PREPRODUCTION_APAC . $binding['url'];
        } elseif ($this->region == Endpoints::REGION_EMEA) {
            return $this->production ? Endpoints::PRODUCTION_EMEA . $binding['url'] : Endpoints::PREPRODUCTION_EMEA . $binding['url'];
        }

        return false;
    }

}
