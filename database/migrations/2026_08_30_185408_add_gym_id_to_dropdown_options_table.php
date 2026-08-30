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
        Schema::table('dropdown_options', function (Blueprint $table) {
            $table->dropUnique(['category', 'label']);
            $table->foreignId('gym_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->unique(['gym_id', 'category', 'label']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dropdown_options', function (Blueprint $table) {
            $table->dropUnique(['gym_id', 'category', 'label']);
            $table->dropConstrainedForeignId('gym_id');
            $table->unique(['category', 'label']);
        });
    }
};
