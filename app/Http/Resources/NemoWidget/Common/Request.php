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
            'id' => $this->resource->id,
            'segments' => $this->resource->data['segments'],
            'passengers' => $this->resource->data['passengers'],
            'parameters' => $this->resource->data['parameters'],
            'url' => URL::route(NemoWidgetCache::FLIGHTS_SEARCH_GET_REQUEST, ['id' => $this->resource->id], false)
        ];

    }
}