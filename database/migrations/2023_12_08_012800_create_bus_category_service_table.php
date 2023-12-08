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

        Schema::create('bus_category_service', function (Blueprint $table) {
            $table->foreignId("bus_category_id");
            $table->foreignId("service_id");
            $table->primary(["bus_category_id", "service_id"]);
        });

        Schema::table('bus_category_service', function (Blueprint $table) {
            $table->foreign("bus_category_id")->references("id")->on("bus_categories")->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign("service_id")->references("id")->on("services")->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bus_category_service');
    }
};
