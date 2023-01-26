<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductDishesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('product_dishes')->delete();
        
        \DB::table('product_dishes')->insert(array (
            0 => 
            array (
                'id' => 34,
                'product_id' => 6,
                'restaurant_dish_id' => 1,
                'quantity' => 1,
                'created_at' => '2023-01-03 15:32:18',
                'updated_at' => '2023-01-03 15:32:18',
            ),
            1 => 
            array (
                'id' => 35,
                'product_id' => 6,
                'restaurant_dish_id' => 2,
                'quantity' => 3,
                'created_at' => '2023-01-03 15:32:18',
                'updated_at' => '2023-01-03 15:32:18',
            ),
            2 => 
            array (
                'id' => 36,
                'product_id' => 7,
                'restaurant_dish_id' => 1,
                'quantity' => 1,
                'created_at' => '2023-01-03 15:33:47',
                'updated_at' => '2023-01-03 15:33:47',
            ),
        ));
        
        
    }
}