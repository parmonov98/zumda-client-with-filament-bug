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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('SET NULL');
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('SET NULL');
            $table->unsignedBigInteger('price')->default(0);
//            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('status')->default('active');
            $table->string('photo_id')->nullable(); // file_id from photos
            $table->integer('profit_in_percentage')->default(0); // file_id from photos
            $table->integer('has_options')->default(false); // file_id from photos
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
        Schema::dropIfExists('products');
    }
};
