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
        Schema::create('order_items', function (Blueprint $table) {
            // Keep auto-increment ID for internal use
            $table->id();
            
            // Add UUID for public/external use
            $table->uuid('uuid')->unique();

            $table->foreignId('customer_account_id')->nullable()->constrained('customer_accounts')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');

            $table->decimal('selling_price', 10, 2)->nullable();
            $table->integer('quantity')->nullable();
            $table->decimal('sub_total', 10, 2)->nullable();

            $table->tinyInteger('order_item_status')->nullable(); // 0=cancelled, 1=bought, 2=pending

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
        Schema::dropIfExists('order_items');
    }
};