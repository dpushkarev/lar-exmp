<?php


namespace App\Adapters;


use Illuminate\Support\Collection;

class XmlAdapter extends NemoWidgetAbstractAdapter
{
    public function parseFaultResponse(string $response)
    {
        $simpleObject = new \SimpleXMLElement($response);
        $simpleObject->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $simpleObject->registerXPathNamespace('common_v49_0', 'http://www.travelport.com/schema/common_v49_0');

        return collect([
            'TransactionId' => current($simpleObject->xpath('//common_v49_0:TransactionId')[0]),
            'Description' => current($simpleObject->xpath('//common_v49_0:Description')[0]),
            'Code' => current($simpleObject->xpath('//common_v49_0:Code')[0]),
        ]);
    }

    /**
     * @param $xml
     * @return Collection
     */
    public function getSegments($xml): Collection
    {
        $simpleObject = new \SimpleXMLElement($xml);
        $simpleObject->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $simpleObject->registerXPathNamespace('air', 'http://www.travelport.com/schema/air_v49_0');
        $airSegments = $simpleObject->xpath('//air:AirSegment');

        return collect($airSegments);
    }

    public function getBookingsByPriceNum($xml, string $price): Collection
    {
        $bookings = collect();
        $priceNum = (int)filter_var($price, FILTER_SANITIZE_NUMBER_INT) - 1;

        $simpleObject = new \SimpleXMLElement($xml);
        $simpleObject->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $simpleObject->registerXPathNamespace('air', 'http://www.travelport.com/schema/air_v49_0');
        $airPricePoint = $simpleObject->xpath('//air:AirPricePoint')[$priceNum];
        $airPricingInfo = $airPricePoint->children('air', true)->AirPricingInfo[0];

        foreach ($airPricingInfo->FlightOptionsList as $FlightOptions) {
            foreach ($FlightOptions as $FlightOption) {
                foreach ($FlightOption->Option as $option) {
                    foreach ($option->BookingInfo as $bookingInfo) {
                        $bookings->add($bookingInfo->attributes());
                    }
                }
            }
        }

        return $bookings;
    }

    public function getAirPriceByNum($xml, string $price)
    {
        $priceNum = (int)filter_var($price, FILTER_SANITIZE_NUMBER_INT) - 1;

        $simpleObject = new \SimpleXMLElement($xml);
        $simpleObject->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $simpleObject->registerXPathNamespace('air', 'http://www.travelport.com/schema/air_v49_0');
        return $simpleObject->xpath('//air:AirPricePoint')[$priceNum];
    }

}