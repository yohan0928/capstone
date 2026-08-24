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
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();  // Already indexed (unique)

            $table->foreignId('owner_account_id')->constrained('owner_accounts')->onDelete('cascade');
            // Add index - frequently used in WHERE clauses

            $table->string('branch_profile')->nullable();
            // Probably NO index - rarely searched by profile

            $table->string('branch_name')->nullable();
            // Add index if you search by name: WHERE branch_name LIKE '%search%'

            $table->string('location')->nullable();
            // Add index if you filter by location

            $table->text('google_map_url')->nullable();
            // NO index - text column, rarely searched

            $table->text('features')->nullable();
            // NO index - text column, rarely searched

            $table->time('open_time')->nullable();
            $table->time('close_time')->nullable();
            // Probably NO index - time ranges are complex to index

            $table->string('open_days')->nullable();
            // Probably NO index - low search frequency

            $table->tinyInteger('branch_status')->nullable();
            // Add index - frequently filtered

            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            // Add index if you sort/filter by dates

            $table->boolean('active')->nullable();
            // Add index if you frequently filter active/inactive

            // Recommended indexes:
            $table->index('owner_account_id');
            $table->index('branch_name');
            $table->index('location');
            $table->index('branch_status');
            $table->index('active');
            $table->index('date_created');
            $table->index(['branch_status', 'active']);  // Composite for common queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
