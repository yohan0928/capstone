<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('cascade');
            $table->foreignId('service_category_id')->constrained('service_categories')->onDelete('cascade');
            $table->foreignId('service_name_id')->constrained('service_names')->onDelete('cascade');
            $table->foreignId('customer_account_id')->constrained('customer_accounts')->onDelete('cascade');
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->onDelete('cascade');
            $table->integer('rating'); // 1-5
            $table->text('comment');
            $table->tinyInteger('approved')->nullable();
            $table->tinyInteger('active')->nullable();
            $table->timestamps();

            $table->index(['service_name_id', 'branch_id', 'service_category_id']);
            $table->index(['approved', 'active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('feedbacks');
    }
};