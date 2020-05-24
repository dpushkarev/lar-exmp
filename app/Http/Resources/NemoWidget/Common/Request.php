<?php


namespace App\Http\Resources\NemoWidget\Common;


use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

class Request extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->resource->getRequestId(),
            'segments' => $this->resource->getSegments(),
            'passengers' => $this->resource->getPassengers(),
            'parameters' => $this->resource->getParameters(),
            'url' => URL::route('flights.search.get.request', ['id' => $this->resource->getRequestId()], false)
        ];

    }
}