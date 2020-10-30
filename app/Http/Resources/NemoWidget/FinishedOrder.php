<?php

namespace App\Http\Resources\NemoWidget;

use Illuminate\Http\Resources\Json\JsonResource;

class FinishedOrder extends JsonResource
{
    public static $wrap;

    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'code' => 301,
            'message' => 'Finished checkout',
            'reservationCode' => $this->resource->code,
        ];
    }
}