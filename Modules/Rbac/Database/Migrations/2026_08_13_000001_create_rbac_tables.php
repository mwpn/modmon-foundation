<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * RBAC-owned schema. `user_id` references the Identity user domain
     * via `UserQueryContract` validation at the service layer — no
     * cross-module foreign key constraint to Identity's `users` table.
     */
    public function up(): void
    {
        Schema::create('rbac_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('rbac_role_permission', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('rbac_roles')->cascadeOnDelete();
            $table->string('permission_id');
            $table->unique(['role_id', 'permission_id']);
        });

        Schema::create('rbac_user_role', function (Blueprint $table) {
            $table->string('user_id');
            $table->foreignId('role_id')->constrained('rbac_roles')->cascadeOnDelete();
            $table->unique(['user_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rbac_user_role');
        Schema::dropIfExists('rbac_role_permission');
        Schema::dropIfExists('rbac_roles');
    }
};
