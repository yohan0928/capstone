
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
        Schema::create('products', function (Blueprint $table) {
            // Keep auto-increment ID for internal use
            $table->id();
            
            // Add UUID for public/external use
            $table->uuid('uuid')->unique();

            $table->foreignId('owner_account_id')->constrained('owner_accounts')->onDelete('cascade'); // 1=owner
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');

            $table->string('product_batch_no')->nullable();

            $table->string('product_img')->nullable();

            $table->string('product_type')->nullable();

            $table->string('product_name')->nullable();

            $table->integer('quantity_in')->nullable();
            $table->string('unit')->nullable();
            $table->integer('quantity_threshold')->nullable();

            $table->string('unit_conversion')->nullable();
            $table->string('converted_unit')->nullable();
            $table->integer('converted_quantity_in')->nullable();

            $table->decimal('selling_price', 10, 2)->nullable();

            $table->timestamp('date_stored')->nullable();
            $table->timestamp('date_expiration')->nullable();

            $table->tinyInteger('product_status')->nullable(); // 0=unavailable, 1=available

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
        Schema::dropIfExists('products');
    }
};
