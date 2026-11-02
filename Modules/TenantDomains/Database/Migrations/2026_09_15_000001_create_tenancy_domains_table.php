<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenancy_domains', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('hostname')->unique();
            $table->boolean('is_primary')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenancy_domains');
    }
};
