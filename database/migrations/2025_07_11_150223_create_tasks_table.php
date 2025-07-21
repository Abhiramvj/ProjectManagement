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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            // THESE ARE THE TWO COLUMNS THAT WERE MISSING/INCOMPLETE
            $table->foreignId('project_id')->constrained()->onDelete('cascade'); // Links to projects table
            $table->foreignId('assigned_to_id')->constrained('users'); // Links to users table

            $table->string('status')->default('todo'); // todo, in-progress, done
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
