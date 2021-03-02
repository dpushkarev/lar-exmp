<?php

namespace App\Nova;

use App\Models\FrontendDomainRule;
use DKulyk\Nova\Tabs;
use Epartment\NovaDependencyContainer\HasDependencies;
use Epartment\NovaDependencyContainer\NovaDependencyContainer;
use Fourstacks\NovaCheckboxes\Checkboxes;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Currency;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use OwenMelbz\RadioField\RadioButton;

class PlatformRule extends Resource
{

    use HasDependencies;


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
            new Tabs('Platform rule', [
                'Main' => [
                    ID::make(__('ID'), 'id')->sortable(),
                    BelongsTo::make('Platform', 'platform', Platform::class),
                    Boolean::make('Active', 'active'),
                ],
                'Conditionals' => [
                    Select::make('Trip type', 'trip_type')
                        ->options([
                            'one_way' => 'One way',
                            'return' => 'Return',
                            'multi' => 'Multi',
                        ])
                        ->rules('required')
                        ->hideFromIndex(),

                    NovaDependencyContainer::make([
                        BelongsTo::make('Origin', 'origin', VocabularyName::class)->searchable()->nullable()->hideFromIndex(),
                        BelongsTo::make('Destination', 'destination', VocabularyName::class)->searchable()->nullable()->hideFromIndex(),
                    ])->dependsOn('trip_type', FrontendDomainRule::ONE_WAY_TYPE)
                        ->dependsOn('trip_type', FrontendDomainRule::RETURN_TYPE),

                    Checkboxes::make('Cabin classes', 'cabin_classes')
                        ->options([
                            'economy' => 'Economy',
                            'premium_economy' => 'Premium Economy',
                            'first' => 'First',
                            'business' => 'Business',
                        ])->hideFromIndex(),
                    Checkboxes::make('Passenger types', 'passenger_types')
                        ->options([
                            'adult' => 'Adult',
                            'child' => 'Child',
                            'infant' => 'Infant',
                        ])->hideFromIndex()->rules([$this->checkboxRule()]),
                    Checkboxes::make('Fare types', 'fare_types')
                        ->options([
                            'public' => 'Public',
                            'nego' => 'Nego',
                            'private' => 'Private',
                        ])->hideFromIndex(),
                    Currency::make('Min. amount' ,'min_amount')->currency('RSD')->hideFromIndex(),
                    Currency::make('Max. amount', 'max_amount')->currency('RSD')->hideFromIndex(),
                    Date::make('From date', 'from_date')->hideFromIndex(),
                    Date::make('To date', 'to_date')->hideFromIndex(),
                ],
                'Fees' => [
                    Number::make('Agency fee', 'agency_fee')->rules('required')->min(0.1)->step(.1)->onlyOnForms(),
                    RadioButton::make('Agency fee type', 'agency_fee_type')
                        ->options([
                            'fix' => ['Fix' => 'RSD'],
                            'percent' => 'Percent'
                        ])->default('fix')->marginBetween()->onlyOnForms(),

                    Text::make('Agency fee', function ($model) {
                        return static::getFormatFee($model->agency_fee, $model->agency_fee_type, $model->platform);
                    })->exceptOnForms(),

                    Number::make('Brand fee', 'brand_fee')->rules('required')->min(0)->step(.1)->onlyOnForms(),
                    RadioButton::make('Brand fee type', 'brand_fee_type')
                        ->options([
                            'fix' => ['Fix' => 'RSD'],
                            'percent' => 'Percent'
                        ])->default('fix')->marginBetween()->onlyOnForms(),

                    Text::make('Brand fee', function ($model) {
                        return static::getFormatFee($model->brand_fee, $model->brand_fee_type, $model->platform);
                    })->exceptOnForms(),

                    Number::make('Cash fee', 'cash_fee')->rules('required')->min(0.1)->step(.1)->onlyOnForms(),
                    RadioButton::make('Cash fee type', 'cash_fee_type')
                        ->options([
                            'fix' => ['Fix' => 'RSD'],
                            'percent' => 'Percent'
                        ])->default('fix')->marginBetween()->onlyOnForms(),

                    Text::make('Cash fee', function ($model) {
                        return static::getFormatFee($model->cash_fee, $model->cash_fee_type, $model->platform);
                    })->exceptOnForms(),

                    Number::make('Intesa fee', 'intesa_fee')->rules('required')->min(0.1)->step(.1)->onlyOnForms(),
                    RadioButton::make('Intesa fee type', 'intesa_fee_type')
                        ->options([
                            'fix' => ['Fix' => 'RSD'],
                            'percent' => 'Percent'
                        ])->default('fix')->marginBetween()->onlyOnForms(),

                    Text::make('Intesa fee', function ($model) {
                        return static::getFormatFee($model->intesa_fee, $model->intesa_fee_type, $model->platform);
                    })->exceptOnForms(),
                ]
            ])
        ];
    }

    private function checkboxRule()
    {
        return function($attribute, $value, $fail) {
            $types = \json_decode($value, true) ;
            foreach ($types as $type) {
                if ($type) return true;
            }

            return $fail('Select at least one value');
        };
    }

    public static function getFormatFee($amount, $type, $platform)
    {
        if ($type == 'fix') {
            return sprintf('%s %s', $platform->currency_code, $amount);
        }

        return sprintf('%s %s', $amount, '%');
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
