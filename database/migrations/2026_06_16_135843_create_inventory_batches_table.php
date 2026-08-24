<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('owner_account_id');
            $table->unsignedBigInteger('branch_id');
            $table->string('item_type');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('ingredient_id')->nullable();
            $table->unsignedBigInteger('inventory_transaction_id');
            $table->decimal('quantity_received', 10, 4);
            $table->decimal('quantity_remaining', 10, 4);
            $table->string('unit')->nullable();
            $table->string('note')->nullable();
            $table->timestamp('received_at');
            $table->timestamps();

            $table->foreign('owner_account_id')->references('id')->on('owner_accounts')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            $table->foreign('ingredient_id')->references('id')->on('ingredients')->onDelete('set null');
            $table->foreign('inventory_transaction_id')->references('id')->on('inventory_transactions')->onDelete('cascade');

            // Short explicit index names to stay under MySQL's 64-char limit
            $table->index(['item_type', 'product_id', 'quantity_remaining'], 'idx_batch_product');
            $table->index(['item_type', 'ingredient_id', 'quantity_remaining'], 'idx_batch_ingredient');
            $table->index(['owner_account_id', 'branch_id', 'received_at'], 'idx_batch_owner_branch');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_batches');
    }
};