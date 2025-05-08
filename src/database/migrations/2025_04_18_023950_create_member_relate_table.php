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
            $table->json('goals')->nullable();
            $table->json('purposes')->nullable();
            $table->string('highest_education');
            $table->json('schools')->nullable();
            $table->json('departments')->nullable();
            $table->json('certificates')->nullable();
            $table->timestamps();
        });

        Schema::create('invitation_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('from_member_id')->nullable()->constrained('members')->onDelete('set null');
            $table->foreignId('to_member_id')->nullable()->constrained('members')->onDelete('set null');
            $table->string('email')->nullable();
            $table->timestamp('expired')->nullable();
            $table->boolean('used')->default(false);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        /** Relation */
        Schema::create('member_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('lang_type_background', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lang_type_id')->constrained('lang_types')->onDelete('cascade')->comment('語言');
            $table->foreignId('background_id')->constrained('backgrounds')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('level_type_background', function (Blueprint $table) {
            $table->id();
            $table->foreignId('level_type_id')->constrained('level_types')->onDelete('cascade')->comment('語言');
            $table->foreignId('background_id')->constrained('backgrounds')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lang_type_background');
        Schema::dropIfExists('level_type_background');
        Schema::dropIfExists('member_role');
        Schema::dropIfExists('invitation_codes');
        Schema::dropIfExists('backgrounds');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('profiles');
        Schema::dropIfExists('members');
    }
};
