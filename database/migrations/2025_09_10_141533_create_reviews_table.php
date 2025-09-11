<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReviewsTable extends Migration
{
    public function up()
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('reviewer_id')->constrained('users')->onDelete('cascade');
            $table->unsignedTinyInteger('review_month'); // 1-12
            $table->unsignedSmallInteger('review_year');
            $table->timestamps();
            $table->unique(['user_id', 'review_month', 'review_year', 'reviewer_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('reviews');
    }
}
