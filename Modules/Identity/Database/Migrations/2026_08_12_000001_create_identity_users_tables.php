<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Identity\Infrastructure\Adoption\UsersTableSchemaValidator;

/**
 * Identity-owned migration for users and password_reset_tokens.
 *
 * Behaves on both Foundation 1.x hosts (adopt existing host scaffolding
 * tables) and Foundation 2.x hosts (create the tables fresh):
 *
 * - neither table exists          -> create both
 * - both exist and compatible     -> adopt without schema/data changes
 * - both exist but incompatible   -> abort with diagnostic
 * - only one exists               -> abort with diagnostic
 *
 * sessions is Foundation-owned and is never touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        $usersExist = Schema::hasTable('users');
        $resetTokensExist = Schema::hasTable('password_reset_tokens');

        if (! $usersExist && ! $resetTokensExist) {
            $this->createTables();

            return;
        }

        if ($usersExist && $resetTokensExist) {
            $errors = app(UsersTableSchemaValidator::class)->validate();

            if (! empty($errors)) {
                throw new RuntimeException(
                    "Identity cannot adopt existing tables. Schema incompatibilities:\n- ".implode("\n- ", $errors)
                );
            }

            return; // adopt: tables exist and match, no schema/data change
        }

        $missing = $usersExist ? 'password_reset_tokens' : 'users';

        throw new RuntimeException(
            "Identity cannot adopt partial table state: table '{$missing}' is missing. "
            .'Both users and password_reset_tokens must exist together before adoption.'
        );
    }

    public function down(): void
    {
        // Non-destructive by design: Identity never drops owned tables in
        // Foundation v1 (no uninstall); data is preserved.
    }

    private function createTables(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }
};
