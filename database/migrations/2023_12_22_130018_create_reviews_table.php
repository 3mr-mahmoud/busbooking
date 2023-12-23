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
        Schema::create('reviews', function (Blueprint $table) {
            $table->foreignId("customer_id");
            $table->foreignId("trip_id");
            $table->text("comment")->nullable();
            $table->integer("stars")->max(5)->min(1);
            $table->primary(['customer_id', 'trip_id']);
            $table->foreign('customer_id')->references("id")->on("customers")->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('trip_id')->references("id")->on("trips")->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamp("created_at")->useCurrent();
            $table->timestamp("seen_at")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reviews');
    }
};
