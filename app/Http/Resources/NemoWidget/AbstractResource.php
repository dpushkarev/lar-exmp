<?php

namespace App\Http\Resources\NemoWidget;

use Illuminate\Http\Resources\Json\JsonResource;

abstract class AbstractResource extends JsonResource
{

    public static $wrap;

    public function withResponse($request, $response)
    {
        $response->setEncodingOptions(JSON_UNESCAPED_UNICODE);
    }

    public function resolve($request = null)
    {
        $result = parent::resolve($request);
        $result['system'] = new System([]);

        return $result;
    }

}