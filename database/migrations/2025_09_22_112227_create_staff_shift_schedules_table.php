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
        Schema::create('staff_shift_schedules', function (Blueprint $table) {
            // Keep auto-increment ID for internal use
            $table->id();
            
            // Add UUID for public/external use
            $table->uuid('uuid')->unique();

            $table->foreignId('owner_account_id')->constrained('owner_accounts')->onDelete('cascade'); // 1=owner
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            $table->foreignId('staff_account_id')->nullable()->constrained('staff_accounts')->onDelete('cascade');

            $table->time('shift_time_start')->nullable();

            $table->time('shift_time_end')->nullable();

            $table->date('shift_date_start')->nullable();

            $table->date('shift_date_end')->nullable();

            $table->tinyInteger('staff_shift_schedule_status')->nullable(); // 0=absent, 1=on-duty, 2=pending, 3=completed, 4=available

            $table->timestamp('date_created')->nullable();

            $table->boolean('active')->nullable(); // 0=no, 1=yes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_shift_schedules');
    }
};
