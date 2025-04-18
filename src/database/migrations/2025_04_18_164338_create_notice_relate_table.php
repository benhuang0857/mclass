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
        // Notice Types Table
        Schema::create('notice_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Unique name for each type
            $table->string('slug')->unique(); // Unique URL slug
            $table->integer('sort')->default(0); // Sort order
            $table->boolean('status')->default(true); // Status: active or inactive
            $table->timestamps();
        });

        // Notices Table
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Notice title
            $table->string('feature_img')->nullable(); // Feature image
            $table->foreignId('notice_type_id') // Foreign key to notice_types
                  ->constrained('notice_types')
                  ->onDelete('cascade');
            $table->text('body'); // Notice body content
            $table->boolean('status')->default(true); // Status: active or inactive
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notices');
        Schema::dropIfExists('notice_types');
    }
};
