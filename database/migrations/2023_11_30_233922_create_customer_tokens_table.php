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
        Schema::create('customer_tokens', function (Blueprint $table) {
            $table->string("token");
            $table->foreignId("customer_id");
            $table->foreign("customer_id")->references("id")->on("customers");
            $table->primary(["token", "customer_id"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_tokens');
    }
};
