<?php


namespace App\Services;


use App\Adapters\ModelAdapter;
use App\Adapters\TravelPortAdapter;
use App\Dto\FlightsSearchRequestDto;
use App\Exceptions\TravelPortException;
use App\Facades\TP;
use App\Models\Airline;
use App\Models\Country;
use App\Models\FlightsSearchRequest;
use App\Models\VocabularyName;
use App\Models\FlightsSearchRequest as FlightsSearchRequestModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class NemoWidgetService
{
    protected $travelPortAdapter;
    protected $modelAdapter;

    public function __construct(TravelPortAdapter $travelPortAdapter, ModelAdapter $modelAdapter)
    {
        $this->travelPortAdapter = $travelPortAdapter;
        $this->modelAdapter = $modelAdapter;
    }

    public function autocomplete($q, $iataCode = null)
    {
        $result = VocabularyName::cacheStatic('getByName', $q);

        if (null !== $iataCode) {
            $result = $result->reject(function ($element) use ($iataCode) {
                return $element->nameable->code === $iataCode;
            });
        }

        return $result;
    }

    /**
     * @return mixed
     */
    public function airlinesAll()
    {
        return Airline::cacheStatic('getAll');
    }

    /**
     * @return mixed
     */
    public function countriesAll()
    {
        return Country::cacheStatic('getAll');
    }

    /**
     * @param FlightsSearchRequestDto $dto
     * @return Collection
     */
    public function flightsSearchRequest(FlightsSearchRequestDto $dto): Collection
    {
        $fsrModel = FlightsSearchRequestModel::forceCreate([
            'data' => $dto
        ]);

        return $this->modelAdapter->flightsSearchRequestAdapt($fsrModel);
    }

    /**
     * @param FlightsSearchRequestModel $request
     * @return \Illuminate\Support\Collection
     * @throws TravelPortException
     */
    public function flightsSearchResult(FlightsSearchRequest $request)
    {
        $requestDto = new FlightsSearchRequestDto(
            $request->data['segments'],
            $request->data['passengers'],
            $request->data['parameters']
        );

        try{
            $lowFareSearchRsp = Cache::rememberForever('result'. $request->id, function () use ($requestDto) {
                return TP::LowFareSearchReq($requestDto);
            });

            $LowFareSearchAdapt = $this->travelPortAdapter->LowFareSearchAdapt($lowFareSearchRsp);
            $LowFareSearchAdapt->put('request', $request);

            $request->transaction_id = $lowFareSearchRsp->getTransactionId();

            return $LowFareSearchAdapt;
        } catch (TravelPortException $travelPortException) {
            $request->transaction_id = $travelPortException->getTransactionId();
            $flightsSearchRequestAdapt = $this->modelAdapter->flightsSearchRequestAdapt($request);
            $flightsSearchRequestAdapt->put('results', collect([
                'info' => collect([
                    'errorCode' => $travelPortException->getCode(),
                    'errorMessageEng' => $travelPortException->getMessage()
                ])
            ]));
            return $flightsSearchRequestAdapt;
        } finally{
            $request->save();
        }
    }

}