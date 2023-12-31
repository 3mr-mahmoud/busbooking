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
        Schema::create('stations', function (Blueprint $table) {
            $table->id();
            $table->string("name")->unique();
            $table->string("description")->nullable();
            $table->string("phone")->nullable();
            $table->timestamp("created_at")->useCurrent();

            $table->foreignId("created_by")->nullable();
        });
        Schema::table('stations', function (Blueprint $table) {
            $table->foreign("created_by")->references("id")->on("admins")->nullOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stations');
    }
};
