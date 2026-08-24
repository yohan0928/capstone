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
        Schema::create('owner_accounts', function (Blueprint $table) {
            // Keep auto-increment ID for internal use
            $table->id();
            
            // Add UUID for public/external use
            $table->uuid('uuid')->unique();

            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('contact_no')->nullable();
            $table->string('address')->nullable();
            $table->string('gcash_qr_code_img')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            
            // for 2fa
            $table->boolean('two_factor_enabled')->default(false);
            $table->timestamp('two_factor_enabled_at')->nullable();

            $table->unsignedTinyInteger('role')->nullable()->default(1); // only 1=owner

            $table->unsignedTinyInteger('regular')->nullable(); // 0=no, 1=yes

            $table->timestamp('date_joined')->nullable();
            $table->timestamp('date_deactivated')->nullable();
            $table->text('reasons')->nullable();

            // Consider using only one status field
            $table->tinyInteger('account_status')->default(1); // 0=Suspended, 1=Verified
            
            // Optional: Add Laravel's default timestamps
            $table->timestamps();
            
            // Optional: Add index for better performance
            $table->index('email');
            $table->index('account_status');

            $table->boolean('active')->nullable()->default(1); // 0=no, 1=yes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('owner_accounts');
    }
};