<?php

namespace App\Http\Controllers;

use App\Http\Resources\NemoWidgetGuide;
use App\Models\Airport;
use App\Models\City;
use App\Models\VocabularyName;
use Illuminate\Routing\Controller as BaseController;

/**
 * Class NemoWidget
 * @package App\Http\Controllers
 */
class NemoWidget extends BaseController
{
    public function autocomplete($q)
    {
        $result = VocabularyName::where('name', 'like', $q . '%')->with(['nameable.city.airports', 'nameable.country'])->get();

        $resultGroup = $result->groupBy('nameable_type');

        if ($resultGroup->get(Airport::class)) {
            return new NemoWidgetGuide($resultGroup->get(Airport::class));
        }

        return new NemoWidgetGuide($resultGroup->get(City::class));
    }
}
