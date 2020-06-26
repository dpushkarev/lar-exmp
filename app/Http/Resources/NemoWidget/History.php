<?php

namespace App\Http\Resources\NemoWidget;

class History extends AbstractResource
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
                    'history' => []
                ]
            ],
        ];
    }

}
