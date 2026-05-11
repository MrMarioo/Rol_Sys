<?php

namespace App\Policies;

use App\Models\Field;
use App\Models\User;

class FieldPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Field $field): bool
    {
        return $user->id === $field->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Field $field): bool
    {
        return $user->id === $field->user_id;
    }

    public function delete(User $user, Field $field): bool
    {
        return $user->id === $field->user_id;
    }

    public function viewData(User $user, Field $field): bool
    {
        return $user->id === $field->user_id;
    }

    public function analyze(User $user, Field $field): bool
    {
        return $user->id === $field->user_id;
    }
}
