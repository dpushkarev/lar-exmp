<?php


namespace App\Adapters;


class XmlAdapter extends NemoWidgetAbstractAdapter
{
    public function parseFaultResponse(string $response)
    {
        $clean_xml = str_ireplace(['SOAP-ENV:', 'SOAP:', 'common_v49_0:'], '', $response);
        $xml = simplexml_load_string($clean_xml);

        return collect((array)$xml->Body->Fault->detail->ErrorInfo);
    }

    public function getSegments($xml, array $segments)
    {
        $airSegments = collect();
        $simpleObject = new \SimpleXMLElement($xml);
        $simpleObject->registerXPathNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
        $simpleObject->registerXPathNamespace('air', 'http://www.travelport.com/schema/air_v49_0');
        $allAirSegments = $simpleObject->xpath('//air:AirSegment');

        foreach ($segments as $segment) {
            $segmentNumber = (int) filter_var($segment, FILTER_SANITIZE_NUMBER_INT) - 1;
           if(isset($allAirSegments[$segmentNumber])) {
               $airSegments->add($allAirSegments[$segmentNumber]);
           }
        }

        return $airSegments;
    }
}