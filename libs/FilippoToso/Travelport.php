<?php

namespace Libs\FilippoToso;

use App\Adapters\XmlAdapter;
use App\Exceptions\TravelPortException;
use App\Logging\TravelPortLogger;
use Exception;
use FilippoToso\Travelport\Endpoints;
use FilippoToso\Travelport\Exceptions\InvalidRegionException;
use Illuminate\Support\Collection;

/**
 * Class Travelport
 * @package App\Services
 */
class Travelport extends \FilippoToso\Travelport\Travelport
{
    /**
     * @param $request
     * @return mixed
     * @throws TravelPortException
     */
    public function execute($request)
    {
        if (is_null($request->getTargetBranch())) {
            $request->setTargetBranch($this->targetBranch);
        }

        $service = $this->getService($request);
        $requestClass = get_class($request);
        if ($this->logger) {
            try {
                $result = $service->__soapCall('service', [$request]);
                $this->logger->setTransactionId($result->getTransactionId());
                $responseClass = get_class($result);
                $this->logger->saveSerializedObject($responseClass, serialize($result));
                return $result;
            } catch (\SoapFault $soapException) {
                try {
                    /** @var  $errorInfo Collection*/
                    $errorInfo = app()->make(XmlAdapter::class)->parseFaultResponse($service->__getLastResponse());
                    $this->logger->setTransactionId($errorInfo->get('TransactionId'));
                    throw TravelPortException::getInstance($errorInfo->get('Description'), $errorInfo->get('Code'), $errorInfo->get('TransactionId'));
                } catch (TravelPortException $travelPortException) {
                    throw $travelPortException;
                } catch (Exception $exception) {
                    throw TravelPortException::getInstance($soapException->getMessage(), $soapException->getCode());
                } finally {
                    $responseClass = str_replace('Req', 'Rsp', $requestClass);
                }
            } finally {
                $this->logger->log($requestClass, $service, $request, $service->__getLastRequest());
                $this->logger->log($responseClass, $service, $request, $service->__getLastResponse());
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
