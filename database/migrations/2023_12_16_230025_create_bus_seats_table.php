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
        Schema::create('bus_seats', function (Blueprint $table) {
            $table->foreignId("bus_id");
            $table->unsignedBigInteger("seat_number");
            $table->text("note")->nullable();
            $table->timestamp("created_at")->useCurrent();
            $table->primary(['bus_id', 'seat_number']);
        });
        Schema::table('bus_seats', function (Blueprint $table) {
            $table->foreign("bus_id")->references("id")->on("buses")->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bus_seats');
    }
};
