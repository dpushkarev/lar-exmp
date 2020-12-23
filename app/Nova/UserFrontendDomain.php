<?php

namespace App\Nova;

use App\Rules\CheckMatching;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;

class UserFrontendDomain extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\UserFrontendDomain::class;

    public static $displayInNavigation = false;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'FrontendDomain.domain';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
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
            BelongsTo::make('Frontend domain', 'FrontendDomain', FrontendDomain::class)
                ->rules('required', new CheckMatching($request->get('user')))
                ->creationRules('unique:user_frontend_domains,frontend_domain_id,NULL,id,user_id,' . $request->get('user'))
                ->updateRules('unique:user_frontend_domains,frontend_domain_id,{{resourceId}},id,user_id,' . $request->get('user')),
            BelongsTo::make('User')->hideFromIndex()
                ->rules('required')
        ];
    }

    public static function label()
    {
        return 'Bindings to domain';
    }

    /**
     * Get the displayable singular label of the resource.
     *
     * @return string
     */
    public static function singularLabel()
    {
        return 'Binding to domain';
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
}
