<?php

namespace App\Actions\Jetstream;

use App\Models\User;
use Laravel\Jetstream\Contracts\DeletesUsers;

final class DeleteUser implements DeletesUsers
{
    public function delete(User $user): void
    {
        $user->tokens->each->delete();
        $user->delete();
    }
}
