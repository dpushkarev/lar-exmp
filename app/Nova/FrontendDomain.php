<?php

namespace App\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class FrontendDomain extends Resource
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
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            ID::make('id')->sortable(),
            Text::make('Domain')->rules('required'),
            Text::make('Description')->hideFromIndex(),
            BelongsTo::make('Travel agency', 'travelAgency', TravelAgency::class)
                ->rules('required'),
        ];
    }

    public static function relatableQuery(NovaRequest $request, $query)
    {
        $resource = $request->resource();
        $parenModel = $request->findParentModel();

        if ($resource == UserFrontendDomain::class && $parenModel instanceof \App\Models\User) {
            return $query->where('travel_agency_id', $parenModel->userTravelAgency->travel_agency_id);
        }

        return $query;
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
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
}
