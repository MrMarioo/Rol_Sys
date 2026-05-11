<?php

namespace App\Policies;

use App\Models\FieldData;
use App\Models\User;

class FieldDataPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, FieldData $fieldData): bool
    {
        return $user->id === $fieldData->field->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, FieldData $fieldData): bool
    {
        return $user->id === $fieldData->field->user_id;
    }

    public function delete(User $user, FieldData $fieldData): bool
    {
        return $user->id === $fieldData->field->user_id;
    }
}