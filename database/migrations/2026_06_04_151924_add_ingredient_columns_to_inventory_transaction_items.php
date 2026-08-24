<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_transaction_items', function (Blueprint $table) {

            // Which type of item this line represents
            $table->enum('item_type', ['product', 'ingredient'])
                  ->default('product')
                  ->after('inventory_transaction_id');

            // Ingredient FK (nullable — only set when item_type = ingredient)
            $table->unsignedBigInteger('ingredient_id')
                  ->nullable()
                  ->after('product_id');

            $table->foreign('ingredient_id')
                  ->references('id')
                  ->on('ingredients')
                  ->nullOnDelete();

            // Receiving unit (e.g. "bottle", "sack") — only for stock-in ingredients
            $table->string('received_unit', 100)->nullable()->after('unit');

            // How many base units equal 1 received unit (e.g. 1 bottle = 1000 ml → factor = 1000)
            $table->decimal('conversion_factor', 10, 4)->nullable()->after('received_unit');

            // quantity × conversion_factor → stored in ingredient base unit
            $table->decimal('base_quantity', 10, 4)->nullable()->after('conversion_factor');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transaction_items', function (Blueprint $table) {
            $table->dropForeign(['ingredient_id']);
            $table->dropColumn([
                'item_type',
                'ingredient_id',
                'received_unit',
                'conversion_factor',
                'base_quantity',
            ]);
        });
    }
};