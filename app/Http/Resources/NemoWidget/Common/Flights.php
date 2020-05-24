<?php


namespace App\Http\Resources\NemoWidget\Common;


use Illuminate\Http\Resources\Json\JsonResource;

class Flights extends JsonResource
{
    public function toArray($request)
    {
        return [
            'search' => [
                'formData' => new FormData($this->resource),
                'request' => new Request($this->resource),
                'results' => new Results($this->resource)
            ]
        ];

    }
}