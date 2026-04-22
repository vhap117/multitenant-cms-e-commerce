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
        Schema::create('landlord_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landlord_users');
    }
};
