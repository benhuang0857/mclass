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
        // Member Table
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('nickname');
            $table->string('account')->unique();
            $table->string('email')->unique();
            $table->boolean('email_valid')->default(false);
            $table->string('password');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        // Profile Table
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->string('lastname');
            $table->string('firstname');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->date('birthday');
            $table->string('job');
            $table->timestamps();
        });

        // Contact Table
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->string('city');
            $table->string('region');
            $table->string('address');
            $table->string('mobile')->unique();
            $table->boolean('mobile_valid')->default(false);
            $table->timestamps();
        });

        // Background Table
        Schema::create('backgrounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->json('lang_types')->nullable();
            $table->json('goals')->nullable();
            $table->json('purposes')->nullable();
            $table->string('level');
            $table->string('highest_education');
            $table->string('school')->nullable();
            $table->string('department')->nullable();
            $table->json('certificates')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backgrounds');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('profiles');
        Schema::dropIfExists('members');
    }
};
