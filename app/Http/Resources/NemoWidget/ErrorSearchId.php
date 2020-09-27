<?php

namespace App\Http\Resources\NemoWidget;

use App\Http\Resources\NemoWidget\Common\FormData;
use App\Http\Resources\NemoWidget\Common\Guide;
use App\Http\Resources\NemoWidget\Common\Request;
use App\Http\Resources\NemoWidget\Common\ResultData;
use App\Http\Resources\NemoWidget\Common\Results;

class ErrorSearchId extends AbstractResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'flights' => [
                'search' => [
                    'formData' => new FormData([]),
                    'request' => new Request($this->resource->get('request')),
                    'results' => new Results(collect(['results' => collect([
                        'info' => collect([
                            'errorCode' => 410,
                            'errorMessageEng' => 'Invalid SearchId'
                        ])
                    ])])),
                    'resultData' => new ResultData([])
                ]
            ],
            $this->merge(new Guide($this->resource))
        ];
    }

}
