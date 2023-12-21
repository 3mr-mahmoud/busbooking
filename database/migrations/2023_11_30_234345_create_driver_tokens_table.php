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
        Schema::create('driver_tokens', function (Blueprint $table) {
            $table->string("token");
            $table->foreignId("driver_id");
            $table->foreign("driver_id")->references("id")->on("drivers")->cascadeOnDelete()->cascadeOnUpdate();
            $table->primary(["token", "driver_id"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_tokens');
    }
};
