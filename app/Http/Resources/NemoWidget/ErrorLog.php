<?php

namespace App\Http\Resources\NemoWidget;

class ErrorLog extends AbstractResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            "error" => [
                "code" => $this->resource
            ]
        ];
    }

    public function withResponse($request, $response)
    {
        parent::withResponse($request, $response);
        $response->setStatusCode(201);
    }

}
