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
        Schema::create('gyms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('subscription_plan')->default('Starter');
            $table->string('subscription_status')->default('trial');
            $table->date('subscription_expires_at')->nullable();
            $table->decimal('monthly_fee', 10, 2)->default(0);
            $table->string('payment_status')->default('pending');
            $table->string('logo_text')->default('Gym CRM Portal');
            $table->string('primary_color', 7)->default('#7357e8');
            $table->string('accent_color', 7)->default('#202126');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gyms');
    }
};
