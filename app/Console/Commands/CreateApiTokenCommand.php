<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

final class CreateApiTokenCommand extends Command
{
    protected $signature = 'api:token {email : Owner of the token} {name=drone : Token label}';

    protected $description = 'Create a Sanctum API token for a drone or sensor of the given user';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error(sprintf('User %s does not exist.', $this->argument('email')));

            return self::FAILURE;
        }

        $token = $user->createToken(name: $this->argument('name'));

        $this->info(sprintf('Token for %s: %s', $user->email, $token->plainTextToken));

        return self::SUCCESS;
    }
}
