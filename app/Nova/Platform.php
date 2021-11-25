<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use OwenMelbz\RadioField\RadioButton;

class Platform extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\FrontendDomain::class;

    public static $group = 'Business';

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'domain';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'domain',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            ID::make('id')->sortable(),
            Text::make('Domain')->rules('required')->help('Without http://, https://'),
            Text::make('Token', 'token')->readonly()->hideWhenCreating()->help('Uses within http requests'),
            Text::make('Description')->hideFromIndex(),
            Select::make('Currency', 'currency_code')->options([
                'RSD' => 'RSD',
                'EUR' => 'EUR'
            ])->rules('required')->help('Applies to all rules'),
            Number::make('Agency fee', 'agency_fee_default')->required()->help('In platform\'s currency. Applied if no one rule is fit'),
            Number::make('Cash fee', 'cash_fee')->min(0.0)->step(.1)->onlyOnForms(),
            RadioButton::make('Cash fee type', 'cash_fee_type')
                ->options([
                    'fix' => ['Fixed' => 'In platform\'s currency'],
                    'percent' => 'Percent'
                ])->default('fix')->marginBetween()->onlyOnForms(),

            Text::make('Cash fee', function ($model) {
                return static::getFormatFee($model->cash_fee, $model->cash_fee_type, $model->currency_code);
            })->exceptOnForms(),

            RadioButton::make('Cash fee calculation', 'cash_fee_calculation')
                ->options([
                    'book' => 'Book',
                    'pax' => 'Pax'
                ])->default('book')->marginBetween()->onlyOnForms(),

            Text::make('Cash fee calculation', function ($model) {
                return $model->cash_fee_calculation;
            })->exceptOnForms(),

            Number::make('Intesa fee', 'intesa_fee')->min(0.0)->step(.1)->onlyOnForms(),
            RadioButton::make('Intesa fee type', 'intesa_fee_type')
                ->options([
                    'fix' => ['Fixed' => 'In platform\'s currency'],
                    'percent' => 'Percent'
                ])->default('fix')->marginBetween()->onlyOnForms(),

            Text::make('Intesa fee', function ($model) {
                return static::getFormatFee($model->intesa_fee, $model->intesa_fee_type, $model->currency_code);
            })->exceptOnForms(),
            BelongsTo::make('Travel agency', 'travelAgency', TravelAgency::class)
                ->rules('required'),
            HasMany::make('Rules', 'rules', PlatformRule::class)
        ];
    }

    public static function relatableQuery(NovaRequest $request, $query)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if ($user->isGod()) {
            return $query;
        }

        return $query->where('travel_agency_id', auth()->user()->userTravelAgency->travel_agency_id);
    }

    /**
     * Get the cards available for the request.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function actions(Request $request)
    {
        return [];
    }

    public static function indexQuery(NovaRequest $request, $query)
    {
        return static::filteredUsers($request, $query);
    }

    public static function detailQuery(NovaRequest $request, $query)
    {
        return static::filteredUsers($request, $query);
    }

    public static function getFormatFee($amount, $type, $currency_code)
    {
        if ($type == 'fix') {
            return sprintf('%s %s', $currency_code, $amount);
        }

        return sprintf('%s %s', $amount, '%');
    }

    private static function filteredUsers(NovaRequest $request, $query)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if ($user->isGod()) {
            return $query;
        }

        if (is_null($user->userTravelAgency)) {
            return $query->noRows();
        }

        return $query->where('travel_agency_id', $user->userTravelAgency->travel_agency_id);
    }

    public static function label()
    {
        return 'Platforms';
    }
}
