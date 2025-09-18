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
       Schema::create('milestone_user', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('milestone_id');
    $table->unsignedBigInteger('session_id')->nullable();
    $table->unsignedBigInteger('user_id');
    $table->integer('progress')->default(0);
    $table->timestamp('unlocked_at')->nullable();
    $table->timestamps();

    $table->unique(['milestone_id', 'user_id']);

    $table->foreign('milestone_id')->references('id')->on('milestones')->onDelete('cascade');
    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    $table->foreign('session_id')->references('id')->on('project_sessions')->onDelete('cascade');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('milestone_user');
    }
};
