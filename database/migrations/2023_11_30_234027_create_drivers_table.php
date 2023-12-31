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
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->bigInteger('phone')->unique();
            $table->string('email')->unique();
            $table->bigInteger('national_id')->unique();
            $table->bigInteger('license_number')->unique();
            $table->string('city');
            $table->decimal('salary');
            $table->string('password');
            $table->timestamp("created_at")->useCurrent();
            $table->foreignId("created_by")->nullable();
        });
        Schema::table('drivers', function (Blueprint $table) {
            $table->foreign("created_by")->references("id")->on("admins")->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
