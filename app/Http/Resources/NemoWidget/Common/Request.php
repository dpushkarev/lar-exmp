<?php


namespace App\Http\Resources\NemoWidget\Common;


use App\Http\Middleware\NemoWidgetCache;
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
            'url' => URL::route(NemoWidgetCache::FLIGHTS_SEARCH_GET_REQUEST, ['id' => $this->resource->getRequestId()], false)
        ];

    }
}