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
        Schema::create('customer_rewards', function (Blueprint $table) {
            // Keep auto-increment ID for internal use
            $table->id();

            // Add UUID for public/external use
            $table->uuid('uuid')->unique();

            $table->foreignId('customer_account_id')->constrained('customer_accounts')->onDelete('cascade');  // 1=owner
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            $table->foreignId('reward_tier_id')->nullable()->constrained('reward_tiers')->onDelete('cascade');

            $table->integer('booking_count')->nullable();

            $table->tinyInteger('claim_status')->nullable();

            $table->text('decline_reason')->nullable();

            $table->timestamp('date_created')->nullable();
            $table->dateTime('date_updated')->nullable();

            $table->boolean('active')->nullable();  // 0=no, 1=yes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_rewards');
    }
};
