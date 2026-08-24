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
         // ── INVENTORY TRANSACTIONS (the transaction header) ──────────────
        // One transaction = one stock in OR one stock out declaration.
        // Stock in: done immediately (no approval needed).
        // Stock out: goes pending → owner approves/rejects → then deducts.
 
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
 
            $table->foreignId('owner_account_id')->constrained('owner_accounts')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
 
            // Auto-generated reference number e.g. SI-2025-0001 / SO-2025-0001
            $table->string('transaction_no')->unique();
 
            // 'stock_in' or 'stock_out'
            $table->enum('type', ['stock_in', 'stock_out']);
 
            // Stock out only: overall reason if all items share one reason
            // Per-item reasons are stored in inventory_transaction_items
            $table->enum('reason', ['expired', 'damaged', 'pulled_out', 'sold'])->nullable();
 
            // Stock in  → status always 'done' on create
            // Stock out → starts 'pending', owner sets 'approved' or 'rejected'
            $table->enum('status', ['pending', 'approved', 'rejected', 'done'])->default('pending');
 
            // Who declared/processed this transaction (staff or owner user id)
            $table->unsignedBigInteger('processed_by_id')->nullable();
            $table->string('processed_by_type')->nullable(); // e.g. App\Models\Staff
            $table->string('processed_by')->nullable();      // display name snapshot
 
            // Owner who approved or rejected
            $table->unsignedBigInteger('approved_by_id')->nullable();
            $table->string('approved_by')->nullable();       // display name snapshot
            $table->timestamp('approved_at')->nullable();
 
            $table->string('rejected_reason')->nullable();
            $table->timestamp('rejected_at')->nullable();
 
            $table->tinyInteger('active')->default(1);
            $table->timestamps();
        });
 
        // ── INVENTORY TRANSACTION ITEMS (line items per transaction) ─────
        // Each row = one product in one transaction.
        // Reason can be per-item (for stock out) or inherit from header.
 
        Schema::create('inventory_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
 
            $table->foreignId('inventory_transaction_id')
                  ->constrained('inventory_transactions')
                  ->cascadeOnDelete();
 
            // The product being stocked in or out
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
 
            $table->integer('quantity');       // qty in or out (always positive)
            $table->string('unit')->nullable(); // unit snapshot at time of transaction
 
            // Stock out per-item reason (overrides header reason if set)
            $table->enum('reason', ['expired', 'damaged', 'pulled_out', 'sold'])->nullable();
 
            $table->string('note')->nullable(); // optional per-item note
 
            $table->tinyInteger('active')->default(1);
            $table->timestamps();
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('inventory_transaction_items');
        Schema::dropIfExists('inventory_transactions');
    }
};