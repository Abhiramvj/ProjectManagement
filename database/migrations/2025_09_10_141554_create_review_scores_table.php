<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReviewScoresTable extends Migration
{
    public function up()
    {
        Schema::create('review_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained('reviews')->onDelete('cascade');
            $table->foreignId('criteria_id')->constrained('performance_criteria')->onDelete('cascade');
            $table->unsignedTinyInteger('score'); // 1-10
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('review_scores');
    }
}
