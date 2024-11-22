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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 20)->unique();
            $table->string('firstname', 50);
            $table->string('lastname', 50);
            $table->string('password', 255);
            $table->string('email', 50)->unique()->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('address', 100)->nullable();
            $table->enum('role', ['reg_user', 'moderator', 'admin']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
