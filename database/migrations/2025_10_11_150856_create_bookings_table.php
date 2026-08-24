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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            // Public identifier
            $table->uuid('uuid')->unique();

            $table->string('booking_ref_no')->nullable();

            // Foreign keys
            $table->foreignId('customer_account_id')->constrained('customer_accounts')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            $table->foreignId('service_category_id')->nullable()->constrained('service_categories')->onDelete('cascade');
            $table->foreignId('service_name_id')->nullable()->constrained('service_names')->onDelete('cascade');
            $table->foreignId('seat_id')->nullable()->constrained('seats')->onDelete('cascade');

            // Base booking time and date
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->date('date_start')->nullable();
            $table->date('date_end')->nullable();

            // --- Extension section (separated time & date) ---
            $table->time('extended_start_time')->nullable();  // When extension starts
            $table->time('extended_end_time')->nullable();  // New end time after extension
            $table->date('extended_date_start')->nullable();  // Date of extension start
            $table->date('extended_date_end')->nullable();  // Date of extension end

            // --- Optional fields for tracking ---
            $table->timestamp('booking_date')->nullable();
            $table->tinyInteger('booking_type')->nullable();  // 0=walk-in, 1=online
            $table->tinyInteger('booking_status')->nullable();  // 0=cancelled, 1=booked, 2=pending, 3=no-show, 4=completed

            $table->boolean('start_reminder_sent')->default(false);
            $table->timestamp('start_reminder_sent_at')->nullable();
            $table->boolean('end_reminder_sent')->default(false);
            $table->timestamp('end_reminder_sent_at')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_type')->nullable();
            $table->timestamp('date_created')->nullable();

            $table->unsignedBigInteger('last_updated_by')->nullable();
            $table->string('last_updated_by_type')->nullable();
            $table->timestamp('last_date_updated')->nullable();

            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('updated_by_type')->nullable();
            $table->timestamp('date_updated')->nullable();

            $table->boolean('active')->nullable();  // 0=no, 1=yes

            // Prevent double booking for same seat at same date/time
            $table->unique(['seat_id', 'date_start', 'start_time', 'booking_status'],
                'unique_active_booking_slot');

            // Index for faster queries
            $table->index(['seat_id', 'date_start', 'start_time']);
            $table->index(['seat_id', 'date_start']);
            $table->index(['booking_status', 'active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
