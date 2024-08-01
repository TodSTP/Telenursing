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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('fname',100);
            $table->string('lname',100);
            $table->string('idcard',13)->unique();
            $table->string('password');

            $table->date('dob')->nullable();
            $table->string('phone',10)->nullable();
            $table->string('address',200)->nullable();
            $table->boolean('status')->default(true); // ใช้ boolean และตั้งค่าเริ่มต้นเป็น true
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
