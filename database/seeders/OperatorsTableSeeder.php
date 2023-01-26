<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class OperatorsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('operators')->delete();
        
        \DB::table('operators')->insert(array (
            0 => 
            array (
                'activation_code' => '1',
                'activation_code_used' => 1,
                'created_at' => '2022-12-10 16:17:31',
                'id' => 1,
                'name' => 'life is too short to waste is',
                'status' => true,
                'telegram_id' => 831079550,
                'temp_client_id' => NULL,
                'updated_at' => '2022-12-10 16:18:32',
                'username' => NULL,
            ),
        ));
        
        
    }
}