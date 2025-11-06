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
            
            // Headers from config/imports.php - orders_file
            $table->date('order_date');
            $table->enum('channel', ['PT', 'Amazon']);
            $table->string('sku')->index();
            $table->text('item_description')->nullable();
            $table->string('origin');
            $table->string('so_num')->index();
            $table->decimal('cost', 10, 2);
            $table->decimal('shipping_cost', 10, 2);
            $table->decimal('total_price', 10, 2);
            
            $table->timestamps();
            
            // Unique constraint based on update_or_create config
            $table->unique(['so_num', 'sku']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
