<?php

namespace App\Http\Resources\NemoWidget\Common;

use App\Models\City;
use Illuminate\Http\Resources\Json\JsonResource;

class AirportList extends JsonResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $airportList = collect();
        foreach ($this->resource->city->airports as $airport) {
            $airportList->put($airport->code, new Airport($airport));
        }

        if($this->resource instanceof City) {
            $airportList->put($this->resource->code, [
                "IATA" => $this->resource->code,
                "cityId" => $this->resource->id,
                "isAggregation" => true,
                "airportRating" => null,
                "baseType" => "airport",
                "properName" => null,
                "properNameEn" => null,
                "name" => __($this->resource->name),
                "nameEn" => $this->resource->name,
                "countryCode" => $this->resource->country_code,
            ]);
        }

        return $airportList;
    }

}