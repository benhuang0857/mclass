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
        // Slideshows Table
        Schema::create('slideshows', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('Slideshow title');
            $table->text('description')->nullable()->comment('Slideshow description');
            $table->string('image_url')->comment('Image URL for the slideshow');
            $table->string('link_url')->nullable()->comment('Click-through URL');

            // Classification
            $table->foreignId('slideshow_type_id')
                  ->nullable()
                  ->constrained('slideshow_types')
                  ->onDelete('set null')
                  ->comment('Slideshow classification type');

            // Scheduling
            $table->timestamp('start_date')->nullable()->comment('Display start date/time');
            $table->timestamp('end_date')->nullable()->comment('Display end date/time');

            // Display settings
            $table->enum('device', ['all', 'mobile', 'desktop', 'tablet'])
                  ->default('all')
                  ->comment('Target device for display');
            $table->integer('display_order')->default(0)->comment('Display sequence order');

            // Status
            $table->enum('status', ['published', 'unpublished', 'draft'])
                  ->default('draft')
                  ->comment('Publication status');

            // Audit trail
            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('members')
                  ->onDelete('set null')
                  ->comment('Creator member ID');

            $table->timestamps();

            // Indexes for performance
            $table->index(['status', 'start_date', 'end_date'], 'status_date_index');
            $table->index('display_order');
            $table->index('device');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slideshows');
    }
};
