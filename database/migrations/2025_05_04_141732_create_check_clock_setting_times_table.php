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
        Schema::create('check_clock_setting_times', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('ck_settings_id', 36);
            $table->string('day', 10);
            $table->time('clock_in');
            $table->time('clock_out');
            $table->time('break_start');
            $table->time('break_end');
            $table->timestamp('created_at');
            $table->timestamp('updated_at')->nullable();
            $table->string('deleted_at', 30)->nullable();

            $table->foreign('ck_settings_id')->references('id')->on('check_clock_settings')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('check_clock_setting_times');
    }
};
