<?php


namespace App\Services;


use App\Adapters\ModelAdapter;
use App\Adapters\ftObjectAdapter;
use App\Adapters\XmlAdapter;
use App\Dto\AirPriceRequestDto;
use App\Dto\FlightsSearchRequestDto;
use App\Exceptions\NemoWidgetServiceException;
use App\Exceptions\TravelPortException;
use App\Facades\TP;
use App\Logging\TravelPortLogger;
use App\Models\Airline;
use App\Models\Country;
use App\Models\FlightsSearchRequest;
use App\Models\FlightsSearchResult;
use App\Models\VocabularyName;
use App\Models\FlightsSearchRequest as FlightsSearchRequestModel;
use FilippoToso\Travelport\Air\LowFareSearchRsp;
use Illuminate\Support\Collection;

class NemoWidgetService
{
    protected $ftObjectAdapter;
    protected $modelAdapter;
    protected $xmlAdapter;

    public function __construct(
        FtObjectAdapter $ftObjectAdapter,
        ModelAdapter $modelAdapter,
        XmlAdapter $xmlAdapter
    )
    {
        $this->ftObjectAdapter = $ftObjectAdapter;
        $this->modelAdapter = $modelAdapter;
        $this->xmlAdapter = $xmlAdapter;
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
     * @return Collection
     */
    public function flightsSearchResult(FlightsSearchRequest $request)
    {
        $requestDto = new FlightsSearchRequestDto(
            $request->data['segments'],
            $request->data['passengers'],
            $request->data['parameters']
        );

        try{
//            $lowFareSearchRsp = Cache::rememberForever('result'. $request->id, function () use ($requestDto) {
//                return TP::LowFareSearchReq($requestDto);
//            });

            $lowFareSearchRsp = TP::LowFareSearchReq($requestDto);

//            print_r('<pre>');
//            print_r($lowFareSearchRsp);die;

            $LowFareSearchAdapt = $this->ftObjectAdapter->LowFareSearchAdapt($lowFareSearchRsp, $request->id);
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

    /**
     * @param FlightsSearchResult $resultModel
     * @throws NemoWidgetServiceException
     */
    public function getFlightInfo(FlightsSearchResult $resultModel)
    {
        $log = TravelPortLogger::getLog(LowFareSearchRsp::class, $resultModel->request->transaction_id);

        if(null === $log) {
            throw NemoWidgetServiceException::getInstance('Log of result was not found');
        }

        $allAirSegments = $this->xmlAdapter->getSegments($log);

        $airSegments = collect();
        $airSegmentKeys = collect();
        foreach ($resultModel->segments as $segmentNumber) {
            $segmentNumber = (int) filter_var($segmentNumber, FILTER_SANITIZE_NUMBER_INT) - 1;
            $airSegment = $allAirSegments[$segmentNumber] ?? null;

            if(null !== $airSegment) {
                $airSegments->add($airSegment);
                $airSegmentKeys->put(getXmlAttribute($airSegment, 'Key'), 1);
            }
        }

        $allBookings = $this->xmlAdapter->getBookingsByPriceNum($log, $resultModel->price);

        $bookings = collect();
        foreach ($allBookings as $booking) {
            if($airSegmentKeys->has(getXmlAttribute($booking, 'SegmentRef'))) {
                $bookings->add($booking);
            }
        }

        $airPriceRequestDto = new AirPriceRequestDto(
            $airSegments,
            $resultModel->request->data['passengers'],
            $bookings
        );

        echo "<pre>";
        print_r($airPriceRequestDto);
        die;
    }


}