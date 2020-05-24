<?php


namespace App\Http\Resources\NemoWidget\Common;


use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

class Results extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->resource->getRequestId(),
            'url' => URL::route('flights.search.results', ['id' => $this->resource->getRequestId()], false)
        ];

    }
}