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
        Schema::create('service_names', function (Blueprint $table) {
            // Keep auto-increment ID for internal use
            $table->id();
            
            // Add UUID for public/external use
            $table->uuid('uuid')->unique();

            $table->foreignId('owner_account_id')->constrained('owner_accounts')->onDelete('cascade'); // 1=owner
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            $table->foreignId('service_category_id')->nullable()->constrained('service_categories')->onDelete('cascade');

            $table->string('service_name')->nullable();

            $table->decimal('price', 8,2)->nullable();

            $table->string('time_duration')->nullable();

            $table->string('space_type')->nullable();

            $table->tinyInteger('service_name_status')->nullable(); // 0=unavailable, 1=available

            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();

            $table->boolean('active')->nullable(); // 0=no, 1=yes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_names');
    }
};
