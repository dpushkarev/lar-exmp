<?php

namespace App\Nova;

use Fourstacks\NovaCheckboxes\Checkboxes;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;

class FrontendDomainRule extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\FrontendDomainRule::class;

    public static $group = 'Settings';

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'Platform rules';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'title',
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
            ID::make(__('ID'), 'id')->sortable(),
            BelongsTo::make('Platform', 'frontendDomain', FrontendDomain::class),
            BelongsTo::make('Origin', 'origin', VocabularyName::class)->searchable()->withSubtitles()->nullable(),
            BelongsTo::make('Destination', 'destination', VocabularyName::class)->searchable()->withSubtitles()->nullable(),
            Checkboxes::make('Cabin classes', 'cabin_classes')
                ->options([
                    'economy' => 'Economy',
                    'premium_economy' => 'Premium Economy',
                    'first' => 'First',
                    'business' => 'Business',
                ]),
            Checkboxes::make('Trip types', 'trip_types')
                ->options([
                    'one_way' => 'One way',
                    'return' => 'Return',
                    'multi' => 'Multi',
                ]),
            Checkboxes::make('Passenger types', 'passenger_types')
                ->options([
                    'adult' => 'Adult',
                    'child' => 'Child',
                    'infant' => 'Infant',
                ]),
            Checkboxes::make('Fare types', 'fare_types')
                ->options([
                    'public' => 'Public',
                    'nego' => 'nego',
                    'private' => 'Private',
                ]),
            Number::make('Min. amount' ,'min_amount'),
            Number::make('Max. amount', 'max_amount'),
            Date::make('From date', 'from_date'),
            Date::make('To date', 'to_date')
        ];
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

    public static function label()
    {
        return 'Platform rules';
    }

}
