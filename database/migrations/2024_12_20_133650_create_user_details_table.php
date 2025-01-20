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
        Schema::create('user_details', function (Blueprint $table) {
            $table->id();
            $table->string('fair_meta')->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->string('phone')->nullable();
            $table->string('company_name')->nullable();
            $table->string('placement')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('inactive');
            $table->longText('scanner_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {   
        if (Schema::hasTable('user_details')) {
            $backupTableName = 'user_details_backup_' . now()->format('Y_m_d_His');
            DB::statement("CREATE TABLE $backupTableName AS SELECT * FROM user_details");
        }

        Schema::dropIfExists('user_details');
    }
};