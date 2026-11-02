<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenancy_tenants', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('tenancy_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenancy_tenants')->restrictOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::create('tenancy_contexts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->foreignId('tenant_id')->constrained('tenancy_tenants')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenancy_contexts');
        Schema::dropIfExists('tenancy_memberships');
        Schema::dropIfExists('tenancy_tenants');
    }
};
