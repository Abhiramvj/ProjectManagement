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
        Schema::create('project_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('topic');
            $table->text('description')->nullable();
            $table->date('date');
            $table->unsignedBigInteger('requester_id');
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->foreign('requester_id')->references('id')->on('users');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
