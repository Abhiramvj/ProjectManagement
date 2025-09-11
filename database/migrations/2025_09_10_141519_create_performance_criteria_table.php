<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceCriteriaTable extends Migration
{
    public function up()
    {
        Schema::create('performance_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('performance_categories')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('max_score')->default(10);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('performance_criteria');
    }
}
