<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Widen item_type so it can store 'product', 'ingredient', and 'mto_product'.
        // Using raw SQL because MySQL ENUM/short VARCHAR columns can't be
        // safely altered through the schema builder without specifying the full type.
        DB::statement("ALTER TABLE inventory_transaction_items MODIFY item_type VARCHAR(20) NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // NOTE: if you already have 'mto_product' rows in the table when rolling back,
        // this will fail or truncate them. Clean up that data first if you ever need to roll back.
        DB::statement("ALTER TABLE inventory_transaction_items MODIFY item_type ENUM('product','ingredient') NOT NULL");
    }
};