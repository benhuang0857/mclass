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

        Schema::create('known_langs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lang_type_id')->constrained('lang_types')->onDelete('cascade')->comment('目前熟悉語言');
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('learning_langs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lang_type_id')->constrained('lang_types')->onDelete('cascade')->comment('欲學習的語言別');
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('level_type_id')->constrained('level_types')->onDelete('cascade')->comment('目前等級(程度)');
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('referral_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referral_source_type_id')->constrained('referral_source_types')->onDelete('cascade')->comment('從哪裡知道我們的');
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_type_id')->constrained('goal_types')->onDelete('cascade')->comment('欲達到的目標');
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('purposes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purpose_type_id')->constrained('purpose_types')->onDelete('cascade')->comment('欲達到的目的');
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('highest_education', function (Blueprint $table) {
            $table->id();
            $table->foreignId('education_type_id')->constrained('education_types')->onDelete('cascade')->comment('最高學歷');
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_type_id')->constrained('school_types')->onDelete('cascade')->comment('就讀學校');
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_type_id')->constrained('department_types')->onDelete('cascade')->comment('就讀科系');
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certificate_type_id')->constrained('certificate_types')->onDelete('cascade')->comment('相關語言證照');
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 刪除關聯表 (pivot tables)
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('schools');
        Schema::dropIfExists('highest_education');
        Schema::dropIfExists('purposes');
        Schema::dropIfExists('goals');
        Schema::dropIfExists('referral_sources');
        Schema::dropIfExists('levels');
        Schema::dropIfExists('learning_langs');
        Schema::dropIfExists('known_langs');
        Schema::dropIfExists('member_role');
        Schema::dropIfExists('invitation_codes');

        // 刪除主表
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('profiles');
        Schema::dropIfExists('members');
    }
};
