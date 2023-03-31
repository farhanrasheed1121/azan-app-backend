<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrayerTimesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prayer_times', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->string('fajr_time')->nullable();
            $table->string('dhuhr_time')->nullable();
            $table->string('asr_time')->nullable();
            $table->string('maghrib_time')->nullable();
            $table->string('isha_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('prayer_times');
    }
}
