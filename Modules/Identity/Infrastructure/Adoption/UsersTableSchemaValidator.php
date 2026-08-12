<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Adoption;

use Illuminate\Support\Facades\Schema;

/**
 * Validates that existing users/password_reset_tokens tables match the
 * schema Identity requires before adoption (ADR-0006: detect-and-validate,
 * never detect-and-assume).
 */
class UsersTableSchemaValidator
{
    private const USERS_REQUIRED_COLUMNS = [
        'id' => 'integer',
        'name' => 'string',
        'email' => 'string',
        'password' => 'string',
        'remember_token' => 'string',
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    private const PASSWORD_RESET_TOKENS_REQUIRED_COLUMNS = [
        'email' => 'string',
        'token' => 'string',
        'created_at' => 'datetime',
    ];

    /**
     * Validate both tables for adoption.
     *
     * @return string[] Error messages (empty = compatible)
     */
    public function validate(): array
    {
        $errors = array_merge(
            $this->validateTable('users', self::USERS_REQUIRED_COLUMNS),
            $this->validateTable('password_reset_tokens', self::PASSWORD_RESET_TOKENS_REQUIRED_COLUMNS),
        );

        return $errors;
    }

    /**
     * @param  array<string, string>  $requiredColumns  column => expected type group
     * @return string[]
     */
    private function validateTable(string $table, array $requiredColumns): array
    {
        if (! Schema::hasTable($table)) {
            return ["Table '{$table}' does not exist."];
        }

        $columns = Schema::getColumnListing($table);
        $errors = [];

        foreach ($requiredColumns as $column => $typeGroup) {
            if (! in_array($column, $columns, true)) {
                $errors[] = "Table '{$table}' is missing required column '{$column}'.";

                continue;
            }

            $actual = Schema::getColumnType($table, $column);
            if (! $this->typeMatches($actual, $typeGroup)) {
                $errors[] = "Table '{$table}' column '{$column}' has incompatible type '{$actual}' (expected {$typeGroup}).";
            }
        }

        return $errors;
    }

    private function typeMatches(string $actual, string $expectedGroup): bool
    {
        $normalized = match ($actual) {
            'varchar', 'text', 'char' => 'string',
            'datetime', 'timestamp', 'date' => 'datetime',
            'integer', 'bigint', 'int', 'smallint', 'mediumint', 'tinyint' => 'integer',
            default => $actual,
        };

        return $normalized === $expectedGroup;
    }
}
