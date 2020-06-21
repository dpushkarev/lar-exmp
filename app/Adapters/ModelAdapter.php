<?php


namespace App\Adapters;


use App\Http\Resources\NemoWidget\Common\City;
use App\Http\Resources\NemoWidget\Common\Country;
use App\Models\Airport as AirportModel;
use App\Models\City as CityModel;
use App\Models\FlightsSearchRequest;
use Illuminate\Support\Collection;

class ModelAdapter extends NemoWidgetAbstractAdapter
{
    /**
     * @param FlightsSearchRequest $fsrModel
     * @return Collection
     */
    public function flightsSearchRequestAdapt(FlightsSearchRequest $fsrModel): Collection
    {
        $countries = collect();
        $cities = collect();
        $airports = collect();

        foreach ($fsrModel->data['segments'] as $segment) {
            $departure = $segment['departure'] ?? null;
            $iataCodeArr = $segment['arrival'] ?? null;

            if (!is_null($departure) && !$airports->has($departure['IATA'])) {
                $model = $departure['isCity'] ? CityModel::class : AirportModel::class;
                $airports->put($departure['IATA'], $model::whereCode($departure['IATA'])->first());
            }

            if (!is_null($iataCodeArr) && !$airports->has($iataCodeArr['IATA'])) {
                $model = $iataCodeArr['isCity'] ? CityModel::class : AirportModel::class;
                $airports->put($iataCodeArr['IATA'], $model::whereCode($iataCodeArr['IATA'])->with(['city', 'country'])->first());
            }
        }

        foreach ($airports as $airport) {
            $countries = $countries->merge(new Country($airport->country));
            $cities[$airport->city->id] = new City($airport->city);
        }

        return collect([
            'cities' => $cities,
            'countries' => $countries,
            'airports' => $airports,
            'request' => $fsrModel
        ]);
    }
}