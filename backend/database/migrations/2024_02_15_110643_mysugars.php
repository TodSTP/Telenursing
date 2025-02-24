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
        Schema::create('mysugars', function (Blueprint $table) {
            $table->id();
            $table->integer('sugarValue')->nullable();
            $table->char('symptom',100)->nullable(); // กำหนดให้เป็นค่า null ได้
            $table->char('note',100)->nullable(); // กำหนดให้เป็นค่า null ได้
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mysugars');
    }
};
