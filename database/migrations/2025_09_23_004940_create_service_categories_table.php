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
        Schema::create('service_categories', function (Blueprint $table) {
            // Keep auto-increment ID for internal use
            $table->id();
            
            // Add UUID for public/external use
            $table->uuid('uuid')->unique();

            $table->foreignId('owner_account_id')->constrained('owner_accounts')->onDelete('cascade'); // 1=owner
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');

            $table->json('service_img')->nullable();

            $table->string('service_category')->nullable();

            $table->tinyInteger('service_category_status')->nullable(); // 0=unavailable, 1=available

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
        Schema::dropIfExists('service_categories');
    }
};
