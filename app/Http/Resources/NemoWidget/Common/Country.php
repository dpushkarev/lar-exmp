<?php

namespace App\Http\Resources\NemoWidget\Common;

use Illuminate\Http\Resources\Json\JsonResource;

class Country extends JsonResource
{

    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            $this->resource->code => [
                "code" => $this->resource->code,
                "name" => __($this->resource->name),
                "nameEn" => $this->resource->name,
            ]
        ];
    }
}