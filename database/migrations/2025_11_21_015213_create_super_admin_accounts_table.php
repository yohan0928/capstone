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
        Schema::create('super_admin_accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('contact_no')->nullable();
            $table->text('address')->nullable();
            $table->string('gcash_qr_code_img')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('regular')->default(1);
            $table->tinyInteger('role')->default(0); // 0 = super admin
            $table->timestamp('date_joined')->useCurrent();
            $table->timestamp('date_deactivated')->nullable();
            $table->text('reasons')->nullable();
            $table->tinyInteger('account_status')->default(1); // 0=suspended, 1=verified, 2=pending
            $table->boolean('active')->default(1);

            $table->timestamps();
            
            // Indexes for better performance
            $table->index('email');
            $table->index('account_status');
            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('super_admin_accounts');
    }
};