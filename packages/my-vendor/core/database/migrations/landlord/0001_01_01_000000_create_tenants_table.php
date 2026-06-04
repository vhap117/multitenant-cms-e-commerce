<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Force this migration to ALWAYS run on the landlord connection.
     * @var string
     */
    protected $connection = 'landlord';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('plan');
            $table->string('domain')->unique()->index();
            $table->string('database')->unique();
            
            // Optional: You can add an 'is_active' or 'suspended_at' column here 
            // to handle your "Suspend Tenant" use case from earlier.
            $table->boolean('is_active')->default(false);

            $table->softDeletes();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};