<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('driver_preferences', function (Blueprint $table) {
            $table->foreignId("station_id");
            $table->foreignId("driver_id");
            $table->primary(['station_id', 'driver_id']);
            $table->foreign('station_id')->references("id")->on("stations")->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('driver_id')->references("id")->on("drivers")->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('driver_preferences');
    }
};
