<?php

namespace App\Policies;

use App\Models\Community;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommunityPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Community $community): bool
    {
        return $user->belongsToCommunity($community);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Community $community): bool
    {
        return $user->ownsCommunity($community);
    }

    /**
     * Determine whether the user can add members.
     */
    public function addCommunityMember(User $user, Community $community): bool
    {
        return $user->ownsCommunity($community);
    }

    /**
     * Determine whether the user can update member permissions.
     */
    public function updateCommunityMember(User $user, Community $community): bool
    {
        return $user->ownsCommunity($community);
    }

    /**
     * Determine whether the user can remove members.
     */
    public function removeCommunityMember(User $user, Community $community): bool
    {
        return $user->ownsCommunity($community);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Community $community): bool
    {
        return $user->ownsCommunity($community);
    }
}