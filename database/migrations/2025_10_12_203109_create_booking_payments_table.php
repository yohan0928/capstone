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
        Schema::create('booking_payments', function (Blueprint $table) {
            // Keep auto-increment ID for internal use
            $table->id();
            
            // Add UUID for public/external use
            $table->uuid('uuid')->unique();

            $table->foreignId('customer_account_id')->constrained('customer_accounts')->onDelete('cascade');  // 3=customer
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->onDelete('cascade');

            $table->timestamp('payment_date')->nullable();

            $table->tinyInteger('payment_category')->nullable(); // 0=extension, 1=regular
            $table->tinyInteger('payment_method')->nullable(); // 0=cash, 1=gcash, 2=debit-card

            $table->decimal('total_amount', 10, 2)->nullable();
            $table->decimal('amount_paid', 10, 2)->nullable();
            $table->decimal('change', 10, 2)->nullable();

            $table->string('gcash_ref_no')->nullable();

            $table->string('gcash_receipt_img')->nullable();

            $table->json('notes')->nullable();

            $table->tinyInteger('payment_status')->nullable();  // 0=cancelled, 1=booked, 2=pending, 3=no-show, 4=completed

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
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_payments');
    }
};
