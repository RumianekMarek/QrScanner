<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->string('qr_code');
            $table->text('note');
            $table->timestamps();

            $table->unique(['user_id', 'qr_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notes');
    }
};
