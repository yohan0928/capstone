<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('staff_checkins', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('staff_account_id')->constrained('staff_accounts')->onDelete('cascade');
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->foreignId('staff_shift_schedule_id')->constrained('staff_shift_schedules')->onDelete('cascade');
            $table->timestamp('checkin_time');
            $table->timestamp('checkout_time')->nullable();
            $table->integer('time_worked')->default(0); // in minutes
            $table->boolean('checkin_status')->default(1); // 1=checked in, 0=checked out
            $table->boolean('active')->default(1);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('staff_checkins');
    }
};