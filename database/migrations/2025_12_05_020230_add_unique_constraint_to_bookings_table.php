<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['seat_id', 'date_start', 'start_time'], 'idx_seat_date_time');
            $table->index(['seat_id', 'date_start'], 'idx_seat_date');
            $table->index(['booking_status', 'active'], 'idx_status_active');

            // Optional: Add index for the checkBookingConflict method
            $table->index(['seat_id', 'date_start', 'start_time', 'booking_status', 'active'],
                'idx_conflict_check');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('idx_seat_date_time');
            $table->dropIndex('idx_seat_date');
            $table->dropIndex('idx_status_active');
            $table->dropIndex('idx_conflict_check');
        });
    }
};
