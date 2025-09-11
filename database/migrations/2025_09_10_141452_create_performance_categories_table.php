<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePerformanceCategoriesTable extends Migration
{
    public function up()
    {
        Schema::create('performance_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedTinyInteger('weight'); // e.g., 40 for 40%
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('performance_categories');
    }
}
