<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration creates the 'notifications' table, which is standard
     * for Laravel's notification system. It's polymorphic, so it can
     * be linked to any of your notifiable models (Owner, Staff, Customer).
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            // This will be a unique ID for the notification itself
            $table->uuid('id')->primary();

            // This stores the class name of the notification
            // e.g., App\Notifications\NewBooking
            $table->string('type');
            
            // These two columns create the polymorphic relationship.
            // notifiable_type: e.g., App\Models\Owner
            // notifiable_id: e.g., 123
            $table->morphs('notifiable');
            
            // A JSON column to store all the notification data
            // (e.g., "Booking #123 has been confirmed")
            $table->text('data');
            
            // A timestamp to mark when the user has read the notification
            $table->timestamp('read_at')->nullable();
            
            // created_at and updated_at timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
