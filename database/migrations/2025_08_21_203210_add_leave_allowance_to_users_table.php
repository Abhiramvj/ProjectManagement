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
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('total_annual_leave', 8, 2)->default(15.0); // Default to 15, or your company standard
            $table->decimal('total_sick_leave', 8, 2)->default(10.0);
            $table->decimal('total_personal_leave', 8, 2)->default(5.0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['total_annual_leave', 'total_sick_leave', 'total_personal_leave']);
        });
    }
};
