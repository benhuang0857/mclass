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
    }
};
