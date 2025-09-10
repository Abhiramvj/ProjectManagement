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
        Schema::create('reviews', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade'); // employee
    $table->foreignId('criteria_id')->constrained('review_criterias')->onDelete('cascade');
    $table->foreignId('reviewer_id')->constrained('users')->onDelete('cascade');
    $table->string('month');
    $table->year('year');
    $table->integer('score')->default(0);
    $table->string('rating')->nullable(); // e.g. Exceeds Expectations
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
