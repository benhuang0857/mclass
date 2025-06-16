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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->string('code')->unique()->comment('訂單代碼');
            $table->decimal('total', 10, 2)->default(0.00)->comment('訂單總金額');
            $table->string('currency', 10)->default('TWD')->comment('貨幣單位');
            $table->text('shipping_address')->nullable()->comment('運送地址');
            $table->text('billing_address')->nullable()->comment('帳單地址');
            $table->text('note')->nullable()->comment('訂單備註');
            $table->enum('status', ['pending', 'processing', 'completed', 'rejected'])->default('pending')->comment('訂單狀態');
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('product_name')->comment('商品名稱');
            $table->integer('quantity')->default(1)->comment('商品數量');
            $table->decimal('price', 10, 2)->default(0.00)->comment('商品單價');
            $table->json('options')->nullable()->comment('商品選項');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('order');
    }
};
