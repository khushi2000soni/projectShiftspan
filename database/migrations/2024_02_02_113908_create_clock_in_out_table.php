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
        Schema::create('clock_in_out', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();    // staff_id
            $table->unsignedBigInteger('shift_id')->nullable();
            $table->datetime('clockin_date')->nullable();
            $table->datetime('clockout_date')->nullable();
            $table->string('clockin_latitude')->nullable();
            $table->string('clockin_longitude')->nullable();
            $table->string('clockin_address')->nullable();
            $table->string('clockout_latitude')->nullable();
            $table->string('clockout_longitude')->nullable();
            $table->string('clockout_address')->nullable();
            $table->tinyInteger('is_active')->default(0)->comment('1=> active, 0=>deactive');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('shift_id')->references('id')->on('shifts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clock_in_out');
    }
};
