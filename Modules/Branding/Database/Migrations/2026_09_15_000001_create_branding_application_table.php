<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branding_application', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('logo_dark_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->string('primary_color', 7)->nullable();
            $table->string('accent_color', 7)->nullable();
            $table->string('login_title')->nullable();
            $table->string('login_subtitle')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branding_application');
    }
};
