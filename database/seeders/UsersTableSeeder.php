<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('users')->delete();
        
        \DB::table('users')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'Murod',
                'last_step' => NULL,
                'last_value' => NULL,
                'last_message_id' => NULL,
                'language' => 'uz',
                'status' => 1,
                'role' => 'administrator',
                'email' => NULL,
                'email_verified_at' => NULL,
                'password' => NULL,
                'two_factor_secret' => NULL,
                'two_factor_recovery_codes' => NULL,
                'remember_token' => NULL,
                'telegram_id' => 94665561,
                'phone_number' => NULL,
                'current_team_id' => NULL,
                'client_id' => NULL,
                'administrator_id' => NULL,
                'operator_id' => NULL,
                'partner_id' => NULL,
                'partner_operator_id' => NULL,
                'driver_id' => NULL,
                'profile_photo_path' => NULL,
                'deleted_at' => NULL,
                'created_at' => '2022-11-30 06:21:11',
                'updated_at' => '2022-12-01 23:56:13',
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'life is too short to waste is',
                'last_step' => NULL,
                'last_value' => NULL,
                'last_message_id' => NULL,
                'language' => 'uz',
                'status' => 1,
                'role' => 'operator',
                'email' => NULL,
                'email_verified_at' => NULL,
                'password' => NULL,
                'two_factor_secret' => NULL,
                'two_factor_recovery_codes' => NULL,
                'remember_token' => NULL,
                'telegram_id' => 831079550,
                'phone_number' => NULL,
                'current_team_id' => NULL,
                'client_id' => 1,
                'administrator_id' => NULL,
                'operator_id' => 1,
                'partner_id' => NULL,
                'partner_operator_id' => NULL,
                'driver_id' => NULL,
                'profile_photo_path' => NULL,
                'deleted_at' => NULL,
                'created_at' => '2022-12-10 16:17:52',
                'updated_at' => '2022-12-10 16:18:32',
            ),
            2 => 
            array (
                'id' => 3,
                'name' => 'zumda admin',
                'last_step' => NULL,
                'last_value' => NULL,
                'last_message_id' => NULL,
                'language' => 'uz',
                'status' => 1,
                'role' => 'administrator',
                'email' => NULL,
                'email_verified_at' => NULL,
                'password' => NULL,
                'two_factor_secret' => NULL,
                'two_factor_recovery_codes' => NULL,
                'remember_token' => NULL,
                'telegram_id' => 1431874474,
                'phone_number' => NULL,
                'current_team_id' => NULL,
                'client_id' => NULL,
                'administrator_id' => NULL,
                'operator_id' => NULL,
                'partner_id' => NULL,
                'partner_operator_id' => NULL,
                'driver_id' => NULL,
                'profile_photo_path' => NULL,
                'deleted_at' => NULL,
                'created_at' => '2022-11-30 06:21:11',
                'updated_at' => '2022-12-01 23:56:13',
            ),
            3 => 
            array (
                'id' => 4,
                'name' => 'admin',
                'last_step' => NULL,
                'last_value' => NULL,
                'last_message_id' => NULL,
                'language' => 'uz',
                'status' => 1,
                'role' => 'administrator',
                'email' => 'admin@domain.uz',
                'email_verified_at' => NULL,
                'password' => '$2y$10$gqmdrs2XmcY2FF753kqXUuKwOMrQKj90ygxM3KwkIlFW2P1M8DWZ6',
                'two_factor_secret' => NULL,
                'two_factor_recovery_codes' => NULL,
                'remember_token' => 'ngAIyaFpLYtXGeORZ7mT8CrnuYDErFceaQmny1Gd67YwNpIODOpLHokURVOE',
                'telegram_id' => NULL,
                'phone_number' => NULL,
                'current_team_id' => NULL,
                'client_id' => NULL,
                'administrator_id' => NULL,
                'operator_id' => NULL,
                'partner_id' => NULL,
                'partner_operator_id' => NULL,
                'driver_id' => NULL,
                'profile_photo_path' => NULL,
                'deleted_at' => NULL,
                'created_at' => '2023-01-02 16:43:02',
                'updated_at' => '2023-01-02 16:43:02',
            ),
        ));
        
        
    }
}