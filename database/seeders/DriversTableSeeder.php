<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DriversTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('drivers')->delete();
        
        \DB::table('drivers')->insert(array (
            0 => 
            array (
                'activation_code' => '-',
                'activation_code_used' => 1,
                'created_at' => '2022-12-02 01:52:02',
                'deleted_at' => NULL,
                'id' => 1,
                'name' => 'x',
                'self_status' => true,
                'status' => true,
                'telegram_id' => 321958473,
                'updated_at' => '2022-12-02 01:56:24',
                'username' => NULL,
            ),
            1 => 
            array (
                'activation_code' => 'KjZrtSXzf7',
                'activation_code_used' => 0,
                'created_at' => '2022-12-02 01:52:34',
                'deleted_at' => NULL,
                'id' => 2,
                'name' => '-',
                'self_status' => true,
                'status' => true,
                'telegram_id' => NULL,
                'updated_at' => '2022-12-02 01:52:34',
                'username' => NULL,
            ),
        ));
        
        
    }
}