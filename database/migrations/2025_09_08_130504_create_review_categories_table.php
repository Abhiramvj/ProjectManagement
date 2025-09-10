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
        Schema::create('review_criterias', function (Blueprint $table) {
    $table->id();
    $table->foreignId('category_id')->constrained('review_categories')->onDelete('cascade');
    $table->string('name'); // e.g. Bugs Reported, Documentation
    $table->integer('max_points')->default(10);
    $table->timestamps();
});

    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_criteria');
    }
};
