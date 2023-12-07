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
        Schema::create('route_station', function (Blueprint $table) {
            $table->foreignId("route_id");
            $table->foreignId("station_id");
            $table->integer("order");
            $table->primary(["route_id", "station_id"]);
        });

        Schema::table('route_station', function (Blueprint $table) {
            $table->foreign("route_id")->references("id")->on("routes")->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign("station_id")->references("id")->on("stations")->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('route_station');
    }
};
