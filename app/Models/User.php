<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    const GOD_TYPE = 'god';
    const ADMIN_TYPE = 'admin';
    const TRAVEL_AGENCY_TYPE = 'travel_agency';
    const TRAVEL_AGENT_TYPE = 'travel_agent';

    const ACTIVE = 1;
    const NOT_ACTIVE = 0;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function isActive(): bool
    {
        return $this->active === static::ACTIVE;
    }

    public function isGod(): bool
    {
        return $this->type === static::GOD_TYPE;
    }

    public function isAdmin(): bool
    {
        return $this->type === static::ADMIN_TYPE;
    }

    public function isTravelAgency(): bool
    {
        return $this->type === static::TRAVEL_AGENCY_TYPE;
    }

    public function isTravelAgent(): bool
    {
        return $this->type === static::TRAVEL_AGENT_TYPE;
    }

}
