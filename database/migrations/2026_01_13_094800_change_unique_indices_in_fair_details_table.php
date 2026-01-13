<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fair_details', function (Blueprint $table) {
            $indexes = collect(Schema::getIndexes('fair_details'))->pluck('name')->all();

            if (in_array('fair_details_fair_meta_unique', $indexes)) {
                $table->dropUnique('fair_details_fair_meta_unique');
            }
            
            if (in_array('fair_details_fair_name_unique', $indexes)) {
                $table->dropUnique('fair_details_fair_name_unique');
            }
        });
    }

    public function down(): void
    {         
    }
};