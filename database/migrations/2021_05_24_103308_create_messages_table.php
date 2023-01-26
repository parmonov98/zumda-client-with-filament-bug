<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->enum('type', ['video', 'photo', 'text', 'audio', 'voice', 'animation', 'document']);
            $table->string('content')->nullable(); // file_id of uploaded file into Telegram server
            $table->mediumText('text')->nullable(); // caption | text
            $table->mediumText('entities')->nullable(); // entities | text
            $table->timestamps();
            $table->index('user_id');
            // $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('messages');
    }
}
