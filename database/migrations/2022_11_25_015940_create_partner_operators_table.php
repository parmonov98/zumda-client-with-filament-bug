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
        Schema::create('partner_operators', function (Blueprint $table) {
            $table->id();

            $table->foreignId('restaurant_id')->nullable()->constrained('restaurants')->onDelete('SET NULL');

            $table->string('name');
            $table->string('username')->nullable();
            $table->unsignedBigInteger('telegram_id')->nullable()->unique();
            $table->string('activation_code')->nullable();
            $table->boolean('activation_code_used')->default(false);
            $table->boolean('status')->default(true);
            $table->string('phone_number')->nullable();
            $table->boolean('self_status')->default(false);

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
        Schema::dropIfExists('partner_operators');
    }
};
