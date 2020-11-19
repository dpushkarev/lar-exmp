<?php

namespace App\Http\Resources\NemoWidget\Common;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class AircraftList extends JsonResource
{

    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $aircraftList = collect();
        foreach ($this->resource as $code => $aircraft) {
            if (! is_null($aircraft)) {
                $aircraftList->put($aircraft->code, new Aircraft($aircraft));
            } else {
                Log::channel('non-existent-code')->error(sprintf('Aircraft non-existent code: %s', $code));
            }
        }

        return $aircraftList;
    }
}