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
        Schema::create('leave_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('leave_type')->nullable();
            $table->decimal('leave_days', 5, 2)->nullable();
            $table->string('day_type')->nullable();
            $table->date('start_date');
            $table->string('start_half_session')->nullable();
            $table->date('end_date');
            $table->string('end_half_session')->nullable();
            $table->text('reason');
            $table->string('supporting_document_path')->nullable();
            $table->string('status')->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->text('comments')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'status'], 'leave_applications_user_status_index');
            $table->index(['start_date', 'end_date'], 'leave_applications_date_range_index');
            $table->index('status', 'leave_applications_status_index');
            $table->index(['user_id', 'start_date', 'end_date'], 'leave_applications_user_dates_index');
            $table->index('approved_by', 'leave_applications_approved_by_index');
            $table->index('leave_type', 'leave_applications_leave_type_index');
            $table->index(['user_id', 'status', 'start_date'], 'leave_applications_balance_calc_index');
        });
    }

    /**
     * Reverse the migrations.

     */
    public function down(): void
    {
        // The down method for a 'create' migration should always be 'dropIfExists'.
        Schema::dropIfExists('leave_applications');
    }
};
