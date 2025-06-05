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
            $table->string('id', 36)->primary();
            $table->string('user_id', 36);
            $table->string('company_id', 36);
            $table->string('ck_settings_id', 36);
            $table->string('branch_id', 36)->nullable();
            $table->string('nik', 16)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->enum('employment_type', ['PKWt', 'Pegawai Tetap', 'contract', 'honorer', 'magang']);
            $table->string('phone_number', 20)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('birth_place', 100)->nullable();
            $table->string('grade', 20)->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('sp_type', 50)->nullable();
            $table->string('job_title', 100)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account_no', 30)->nullable();
            $table->string('bank_account_owner', 100)->nullable();
            $table->char('gender', 1);
            $table->text('address')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('company')->onDelete('cascade');
            $table->foreign('ck_settings_id')->references('id')->on('check_clock_settings')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branch')->onDelete('set null');
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
