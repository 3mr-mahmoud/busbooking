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
        Schema::create('buses', function (Blueprint $table) {
            $table->id();
            $table->string("plate_number", 7)->unique();
            $table->string("model")->nullable();
            $table->decimal("capacity")->nullable();
            $table->timestamp("created_at")->useCurrent();

            $table->foreignId("created_by")->nullable();
            $table->foreignId("bus_category_id")->nullable();
        });
        Schema::table('buses', function (Blueprint $table) {
            $table->foreign("created_by")->references("id")->on("admins")->nullOnDelete()->cascadeOnUpdate();
            $table->foreign("bus_category_id")->references("id")->on("bus_categories")->nullOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('buses');
    }
};
