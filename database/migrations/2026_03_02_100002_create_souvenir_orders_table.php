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
        Schema::create('souvenir_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_address_id')->nullable()->constrained('user_addresses')->onDelete('set null');
            $table->date('requested_delivery_date');
            $table->string('delivery_location')->nullable()->comment('Location of delivery (text)');
            $table->dateTime('expected_delivery_at')->nullable()->comment('Expected delivery date and time');
            $table->boolean('delivery_too_close')->default(false)->comment('True when we show "place a request" message');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->boolean('within_city')->default(false)->comment('Delivery within city limits for free shipping');
            $table->enum('status', ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled', 'request_review'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('requested_delivery_date');
        });

        Schema::create('souvenir_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('souvenir_order_id')->constrained('souvenir_orders')->onDelete('cascade');
            $table->foreignId('souvenir_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();

            $table->index('souvenir_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('souvenir_order_items');
        Schema::dropIfExists('souvenir_orders');
    }
};
