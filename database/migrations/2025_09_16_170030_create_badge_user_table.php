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
        Schema::create('badge_user', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('badge_id');
    $table->unsignedBigInteger('user_id');
    $table->unsignedBigInteger('session_id')->nullable();

    $table->timestamp('unlocked_at')->nullable();
    $table->timestamps();
    $table->unique(['badge_id', 'user_id']);
    $table->foreign('badge_id')->references('id')->on('badges')->onDelete('cascade');
    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    $table->foreign('session_id')->references('id')->on(table: 'project_sessions')->onDelete('set null');

});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('badge_user');
    }
};
