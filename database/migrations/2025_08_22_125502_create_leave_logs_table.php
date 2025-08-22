<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('leave_application_id')->nullable()->constrained('leave_applications')->nullOnDelete();

            $table->string('action');
            $table->text('description');
            $table->json('details')->nullable();

            $table->timestamps();

            // ADD INDEXES FOR FASTER QUERIES
            $table->index('user_id');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_logs');
    }
};
