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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            $table->string('last_step')->nullable();
            $table->string('last_value')->nullable();
            $table->string('last_message_id')->nullable();
//            $table->enum('language', ['uz', 'ru'])->default('uz');
            $table->string('language')->default('uz');
//            $table->enum('role', ['user', 'driver', 'operator', 'administrator', 'partner', 'partner_operator', 'developer'])->default('user'); //  person in the system
            $table->boolean('status')->default(1);
            $table->string('role')->default('user');

            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->unsignedBigInteger('telegram_id')->nullable();
            $table->unsignedBigInteger('phone_number')->nullable();
            $table->foreignId('current_team_id')->nullable();
            $table->foreignId('client_id')->nullable();
            $table->foreignId('administrator_id')->nullable();
            $table->foreignId('operator_id')->nullable();
            $table->foreignId('partner_id')->nullable();
            $table->foreignId('partner_operator_id')->nullable();
            $table->foreignId('driver_id')->nullable();
            $table->string('profile_photo_path', 2048)->nullable();
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
        Schema::dropIfExists('users');
    }
};
