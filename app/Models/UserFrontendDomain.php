<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * Class UserFrontendDomain
 * @package App\Models
 */
class UserFrontendDomain extends Model
{
    public function frontendDomain()
    {
        return $this->belongsTo(FrontendDomain::class, 'frontend_domain_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

}