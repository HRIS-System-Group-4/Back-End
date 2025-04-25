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
        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('ck_settings_id');
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->char('gender', 1);
            $table->text('address');

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('ck_settings_id')->references('id')->on('check_clock_settings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
