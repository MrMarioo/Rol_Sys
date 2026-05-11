<?php

namespace App\Policies;

use App\Models\Analytics;
use App\Models\User;

class AnalyticsPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Analytics $analytics): bool
    {
        return $user->id === $analytics->field->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Analytics $analytics): bool
    {
        return $user->id === $analytics->field->user_id;
    }

    public function delete(User $user, Analytics $analytics): bool
    {
        return $user->id === $analytics->field->user_id;
    }

    public function download(User $user, Analytics $analytics): bool
    {
        return $user->id === $analytics->field->user_id;
    }
}