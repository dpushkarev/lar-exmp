<?php

namespace App\Nova;

use App\Nova\Filters\UserType;
use Epartment\NovaDependencyContainer\NovaDependencyContainer;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Gravatar;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Password;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use App\Models\TravelAgency;
use App\Models\User as UserModel;
use Laravel\Nova\Http\Requests\NovaRequest;

class User extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = UserModel::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id', 'name', 'email',
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
            ID::make()->sortable(),

            Gravatar::make()->maxWidth(50),

            Text::make('Name')
                ->sortable()
                ->rules('required', 'max:255'),

            Text::make('Email')
                ->sortable()
                ->rules('required', 'email', 'max:254')
                ->creationRules('unique:users,email')
                ->updateRules('unique:users,email,{{resourceId}}'),

            Password::make('Password')
                ->onlyOnForms()
                ->creationRules('required', 'string', 'min:8')
                ->updateRules('nullable', 'string', 'min:8'),

            Boolean::make('Active'),

            Select::make('Role', 'type')->options([
//                    UserModel::GOD_TYPE => 'God',
    //                UserModel::ADMIN_TYPE => 'Admin',
                    UserModel::TRAVEL_AGENCY_TYPE => 'Travel agency',
                    UserModel::TRAVEL_AGENT_TYPE => 'Travel agent',
                ])
                ->withMeta(['extraAttributes' => [
                    'readonly' => !$request->user()->isGod()
                ]])
                ->rules('required', function($attribute, $value, $fail) use ($request) {
                    if (!$request->user()->isGod() &&
                        $value != UserModel::TRAVEL_AGENT_TYPE
                    ) {
                        return $fail('You can create user with agent role only');
                    }
                })
                ->default(UserModel::TRAVEL_AGENT_TYPE)
                ->displayUsingLabels(),

            NovaDependencyContainer::make([
                Select::make('Travel agency', 'userTravelAgency.travel_agency_id')
                    ->options(TravelAgency::all()->mapWithKeys(function ($item) {
                        return [$item['id'] => $item['title']];
                    }))
                    ->readonly(function ($request) {
                        return !$request->user()->isGod();
                    })
                    ->required()
                    ->default($request->user()->isGod() ? '' : $request->user()->userTravelAgency->travel_agency_id)
                    ->displayUsingLabels()
            ])->dependsOn('type', UserModel::TRAVEL_AGENT_TYPE)
                ->dependsOn('type', UserModel::TRAVEL_AGENCY_TYPE)
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
        return [
            UserType::make()
        ];
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

    private static function filteredUsers(NovaRequest $request, $query)
    {
        if ($request->user()->isGod()) {
            return $query;
        }

        return $query->belongToTravelAgency($request->user()->userTravelAgency->travel_agency_id);
    }

}
