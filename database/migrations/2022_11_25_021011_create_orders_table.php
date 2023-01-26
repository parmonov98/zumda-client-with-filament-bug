<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('SET NULL');
            $table->foreignId('client_id')->nullable()->constrained('clients')->onDelete('SET NULL');
            $table->foreignId('driver_id')->nullable()->constrained('users')->onDelete('SET NULL');
            $table->unsignedFloat('distance')->default(0.0);
//            $table->enum('shipment', ['pickup', 'delivery'])->default('delivery');
            $table->string('shipment')->default('delivery');
//            $table->enum('status', ['created', 'accepted', 'preparing', 'prepared',  'delivering', 'paid', 'delivered', 'completed', 'canceled'])->default('created');
//            'created', 'accepted', 'preparing', 'prepared',  'delivering', 'paid', 'delivered', 'completed', 'canceled'
            $table->string('status')->default('created');
//            $table->enum('payment_type', ['click', 'payme', 'cash'])->nullable();
            $table->string('payment_type')->default('cash');
            $table->unsignedBigInteger('per_km_price')->nullable();
            $table->unsignedBigInteger('shipping_price')->nullable();
            $table->unsignedBigInteger('summary')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('address')->nullable();
            $table->string('longitude')->nullable();
            $table->string('latitude')->nullable();
            $table->string('customer_note')->nullable();
            $table->foreignId('restaurant_id')->nullable()->constrained('restaurants')->onDelete('SET NULL');

            $table->boolean('is_assigned_by_operator')->default(false);
            $table->boolean('is_accepted_order_by_driver')->default(false);
            $table->foreignId('restaurant_operator_id')->nullable()->constrained('partner_operators')->onDelete('SET NULL');
            $table->boolean('is_sent_to_drivers')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
};
