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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId("bus_id");
            $table->foreignId("route_id");
            $table->foreignId("driver_id");

            $table->timestamp("created_at")->useCurrent();
            $table->timestamp("departure_time");
            $table->timestamp("arrival_time")->nullable();
            $table->decimal("price");
            $table->integer("expected_duration")->comment("in minutes");
            $table->timestamp("actual_departure_time")->nullable();

            $table->unsignedBigInteger("golden_seat_number")->nullable();

            $table->foreignId("created_by")->nullable();
        });
        Schema::table('trips', function (Blueprint $table) {
            $table->foreign("created_by")->references("id")->on("admins")->nullOnDelete()->cascadeOnUpdate();
            $table->foreign("bus_id")->references("id")->on("buses")->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign("route_id")->references("id")->on("routes")->restrictOnDelete()->cascadeOnUpdate();
            $table->foreign("driver_id")->references("id")->on("drivers")->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trips');
    }
};
