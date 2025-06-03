<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('company', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('company_username', 255)->unique();
            $table->string('company_name', 255); // ← nama panjang
            $table->string('description', 255)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->integer('location_radius')->default(100);
            $table->boolean('subscription_active')->default(false);
            $table->timestamp('subscription_expires_at')->nullable(); // radius meter
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company');
    }
};
