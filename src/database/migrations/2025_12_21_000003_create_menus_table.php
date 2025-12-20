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
        // Menus Table
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->comment('Menu display name');
            $table->string('icon', 100)->nullable()->comment('Icon class (e.g., fa-home, mdi-dashboard)');
            $table->string('url', 500)->nullable()->comment('Menu link URL (absolute or relative)');
            $table->enum('target', ['_self', '_blank'])->default('_self')->comment('Link opening method');
            $table->foreignId('parent_id')->nullable()->constrained('menus')->onDelete('cascade')->comment('Parent menu ID for hierarchy');
            $table->integer('display_order')->default(0)->comment('Sort order for menu display');
            $table->boolean('status')->default(true)->comment('Enable/disable menu item');
            $table->text('note')->nullable()->comment('Additional notes or description');
            $table->timestamps();

            // Indexes for performance
            $table->index(['parent_id']);
            $table->index(['display_order']);
            $table->index(['status']);
        });

        // Menu-Role Junction Table
        Schema::create('menu_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->onDelete('cascade');
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->timestamps();

            // Prevent duplicate associations
            $table->unique(['menu_id', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_role');
        Schema::dropIfExists('menus');
    }
};
