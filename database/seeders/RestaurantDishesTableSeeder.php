<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RestaurantDishesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('restaurant_dishes')->delete();
        
        \DB::table('restaurant_dishes')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'tovoqcha',
                'restaurant_id' => 1,
                'created_at' => '2023-01-03 15:01:20',
                'updated_at' => '2023-01-03 15:01:20',
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'katta tovoq',
                'restaurant_id' => 1,
                'created_at' => '2023-01-03 15:01:33',
                'updated_at' => '2023-01-03 15:01:33',
            ),
        ));
        
        
    }
}