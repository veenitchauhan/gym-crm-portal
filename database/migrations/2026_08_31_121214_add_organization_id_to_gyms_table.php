<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('gyms', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        DB::table('gyms')->orderBy('id')->each(function (object $gym): void {
            $organizationId = DB::table('organizations')->insertGetId([
                'name' => $gym->name,
                'multi_location_enabled' => false,
                'created_at' => $gym->created_at,
                'updated_at' => $gym->updated_at,
            ]);

            DB::table('gyms')->where('id', $gym->id)->update(['organization_id' => $organizationId]);
        });

        Schema::table('gyms', function (Blueprint $table) {
            $table->unsignedBigInteger('organization_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gyms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organization_id');
        });
    }
};
