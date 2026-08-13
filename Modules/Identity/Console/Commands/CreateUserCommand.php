<?php

declare(strict_types=1);

namespace Modules\Identity\Console\Commands;

use Illuminate\Console\Command;
use Modules\Identity\Models\User;

class CreateUserCommand extends Command
{
    protected $signature = 'identity:user:create
                            {--name= : User full name}
                            {--email= : User email address (unique)}
                            {--password= : Password (hashed by the model cast)}';

    protected $description = 'Create a user on a fresh host (no roles, no admin semantics)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->option('name');
        $email = $this->option('email');
        $password = $this->option('password');

        if ($name === null || $email === null || $password === null) {
            $this->error('All options are required: --name, --email, --password.');

            return self::FAILURE;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("Invalid email address: {$email}");

            return self::FAILURE;
        }

        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');

            return self::FAILURE;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->error("A user with email '{$email}' already exists.");

            return self::FAILURE;
        }

        User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        $this->info("User '{$email}' created successfully.");

        return self::SUCCESS;
    }
}
