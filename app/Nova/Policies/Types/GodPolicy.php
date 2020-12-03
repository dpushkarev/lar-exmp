<?php


namespace App\Nova\Policies\Types;


use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

abstract class GodPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user)
    {
        return $this->checkPermission($user);
    }

    /**
     * @param User $user
     * @return bool
     */
    public function view(User $user)
    {
        return $this->checkPermission($user);
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $this->checkPermission($user);
    }

    /**
     * @param User $user
     * @param $model
     * @return bool
     */
    public function update(User $user, $model)
    {
        return $this->checkPermission($user, $model);
    }

    /**
     * @param User $user
     * @param $model
     * @return bool
     */
    public function delete(User $user, $model)
    {
        return $this->checkPermission($user, $model);
    }

    /**
     * @param User $user
     * @param $model
     * @return bool
     */
    public function restore(User $user, $model)
    {
        return $this->checkPermission($user, $model);
    }

    /**
     * @param User $user
     * @param $model
     * @return bool
     */
    public function forceDelete(User $user, $model)
    {
        return $this->checkPermission($user, $model);
    }

    /**
     * @param User $user
     * @param null $model
     * @return bool
     */
    protected function checkPermission(User $user, $model = null)
    {
        return $user->isGod();
    }
}