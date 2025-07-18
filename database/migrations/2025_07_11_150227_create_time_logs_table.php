<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
     public function up(): void {
        Schema::create('time_logs', function (Blueprint $table) {
            $table->id(); // Each entry is unique by its own ID.
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('set null');
            $table->date('work_date');
            
            // This column type is correct for decimal hours.
            $table->decimal('hours_worked', 5, 2);

            $table->text('description')->nullable();
            $table->timestamps();
            
            
        });
    }

    public function down(): void {
        Schema::dropIfExists('time_logs');
    }
};