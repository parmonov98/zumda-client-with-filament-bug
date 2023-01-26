<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CartsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('carts')->delete();
        
        \DB::table('carts')->insert(array (
            0 => 
            array (
                'created_at' => '2022-11-30 06:21:11',
                'id' => 1,
                'summary' => 0,
                'updated_at' => '2022-11-30 06:21:11',
                'user_id' => 2,
            ),
            1 => 
            array (
                'created_at' => '2022-12-02 01:38:54',
                'id' => 7,
                'summary' => 0,
                'updated_at' => '2022-12-02 01:38:54',
                'user_id' => 25,
            ),
            2 => 
            array (
                'created_at' => '2022-12-02 01:52:17',
                'id' => 8,
                'summary' => 0,
                'updated_at' => '2022-12-02 01:52:17',
                'user_id' => 26,
            ),
            3 => 
            array (
                'created_at' => '2022-12-03 05:35:51',
                'id' => 9,
                'summary' => 0,
                'updated_at' => '2022-12-03 05:35:51',
                'user_id' => 27,
            ),
        ));
        
        
    }
}