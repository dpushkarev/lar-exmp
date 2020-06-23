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
            $this->mergeWhen($this->resource, [
                'id' => $this->resource->id ?? 0,
                'url' => URL::route(NemoWidgetCache::FLIGHTS_SEARCH_GET_REQUEST, ['id' => $this->resource->id ?? 0], false),
            ]),
            'segments' => $this->resource->data['segments'] ?? null,
            'passengers' => $this->resource->data['passengers'] ?? null,
            'parameters' => $this->resource->data['parameters'] ?? null,
        ];

    }
}