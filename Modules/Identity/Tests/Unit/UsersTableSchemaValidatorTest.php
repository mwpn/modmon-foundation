<?php

declare(strict_types=1);

namespace Modules\Identity\Tests\Unit;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Identity\Infrastructure\Adoption\UsersTableSchemaValidator;
use Tests\TestCase;

class UsersTableSchemaValidatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        parent::tearDown();
    }

    public function test_validates_compatible_schema(): void
    {
        $this->createCompatibleTables();

        $errors = app(UsersTableSchemaValidator::class)->validate();

        $this->assertSame([], $errors);
    }

    public function test_reports_missing_table(): void
    {
        $errors = app(UsersTableSchemaValidator::class)->validate();

        $this->assertNotEmpty($errors);
        $this->assertTrue(
            collect($errors)->contains(fn ($e) => str_contains($e, "Table 'users' does not exist.")),
        );
    }

    public function test_reports_missing_column(): void
    {
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('password_reset_tokens', function ($table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        $errors = app(UsersTableSchemaValidator::class)->validate();

        $this->assertNotEmpty($errors);
        $this->assertTrue(
            collect($errors)->contains(fn ($e) => str_contains($e, "Table 'users' is missing required column 'password'.")),
        );
    }

    public function test_reports_incompatible_column_type(): void
    {
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->integer('password'); // wrong type: must be string
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('password_reset_tokens', function ($table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        $errors = app(UsersTableSchemaValidator::class)->validate();

        $this->assertNotEmpty($errors);
        $this->assertTrue(
            collect($errors)->contains(fn ($e) => str_contains($e, "column 'password' has incompatible type")),
        );
    }

    private function createCompatibleTables(): void
    {
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function ($table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        DB::table('users')->insert([
            'name' => 'Legacy User',
            'email' => 'legacy@example.com',
            'password' => bcrypt('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
