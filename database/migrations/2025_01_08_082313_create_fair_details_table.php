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
        Schema::create('fair_details', function (Blueprint $table) {
            $table->id();
            $table->string('fair_meta')->unique()->nullable();
            $table->string('domain')->unique();
            $table->string('fair_name')->unique()->nullable();
            $table->string('fair_start')->nullable();
            $table->string('fair_end')->nullable();
            $table->text('qr_details')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('fair_details')) {
            $backupTableName = 'fair_details_backup_' . now()->format('Y_m_d_His');
            DB::statement("CREATE TABLE $backupTableName AS SELECT * FROM fair_details");
        }

        Schema::dropIfExists('fair_details');
    }
};
