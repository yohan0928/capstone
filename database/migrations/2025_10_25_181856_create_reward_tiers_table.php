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
        Schema::create('reward_tiers', function (Blueprint $table) {
            // Keep auto-increment ID for internal use
            $table->id();
            
            // Add UUID for public/external use
            $table->uuid('uuid')->unique();

            $table->foreignId('owner_account_id')->constrained('owner_accounts')->onDelete('cascade');  // 1=owner  
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');

            $table->tinyInteger('booking_type')->nullable(); // 0=streak, 1=frequent

            $table->integer('booking_required')->nullable();

            $table->text('reward_description')->nullable();

            $table->date('date_start')->nullable();
            $table->date('date_end')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            $table->tinyInteger('reward_tier_status')->nullable(); // 0=unavailable, 1=available

            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_type')->nullable();
            $table->timestamp('date_created')->nullable();

            $table->unsignedBigInteger('last_updated_by')->nullable();
            $table->string('last_updated_by_type')->nullable();
            $table->timestamp('last_date_updated')->nullable();

            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('updated_by_type')->nullable();
            $table->timestamp('date_updated')->nullable();

            $table->boolean('active')->nullable(); // 0=no, 1=yes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_tiers');
    }
};
