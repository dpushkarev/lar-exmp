<?php

namespace App\Nova;

use App\Nova\Filters\UserType;
use Epartment\NovaDependencyContainer\NovaDependencyContainer;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Gravatar;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\HasOne;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Password;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
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
                    UserModel::TRAVEL_AGENCY_TYPE => 'Travel agency',
                    UserModel::TRAVEL_AGENT_TYPE => 'Travel agent',
                ])
                ->withMeta(['extraAttributes' => [
                    'readonly' => !$request->user()->isGod()
                ]])
                ->creationRules('required', function($attribute, $value, $fail) use($request) {
                    if (!$request->user()->isGod() &&
                        $value != UserModel::TRAVEL_AGENT_TYPE
                    ) {
                        return $fail('You can create user with agent role only');
                    }
                })
                ->default(UserModel::TRAVEL_AGENT_TYPE)
                ->displayUsingLabels(),

             NovaDependencyContainer::make([
                 Text::make('Travel Agency', 'userTravelAgency.travelAgency.title')
             ])->dependsOn('type', UserModel::TRAVEL_AGENT_TYPE)
                ->dependsOn('type', UserModel::TRAVEL_AGENCY_TYPE)
                 ->exceptOnForms(),

            HasOne::make('Bind to travel agency', 'UserTravelAgency', UserTravelAgency::class)
                ->canSee(function () {
                    return $this->belongsToTravelAgency();
                }),

            HasMany::make('Bind to platform', 'UserFrontendDomains', UserFrontendDomain::class)
                ->canSee(function () {
                    return $this->isTravelAgent() && $this->hasBindingToTravelAgency();
                }),
        ];
    }

    public static function relatableQuery(NovaRequest $request, $query)
    {
        $resource = $request->resource();

        if ($resource == UserFrontendDomain::class) {
            return $query->where('type', \App\Models\User::TRAVEL_AGENT_TYPE);
        }

        if ($resource == UserTravelAgency::class) {
            return $query->where('type', \App\Models\User::TRAVEL_AGENCY_TYPE)
                ->orWhere('type', \App\Models\User::TRAVEL_AGENT_TYPE);
        }

        return $query;
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
        /** @var \App\Models\User $user */
        $user = $request->user();

        if ($user->isGod()) {
            return $query;
        }

        if (is_null($user->userTravelAgency)) {
            return $query->noRows();
        }

        return $query->hasTravelAgency($request->user()->userTravelAgency->travel_agency_id);
    }

}
