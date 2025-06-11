<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('company_id', 36);
            $table->string('branch_name', 255);
            $table->string('location', 255)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->integer('location_radius')->default(100);
            $table->string('address', 255);
            $table->string('city', 50);
            $table->string('country', 50);
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('company_id')->references('id')->on('company')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch');
    }
};
