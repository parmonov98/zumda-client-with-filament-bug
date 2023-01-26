<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('products')->delete();
        
        \DB::table('products')->insert(array (
            0 => 
            array (
                'id' => 6,
                'user_id' => 4,
                'category_id' => 1,
                'price' => 50000,
                'status' => '1',
                'photo_id' => 'AgACAgIAAxkBAAI3B2OJPwXkT1Q2gWHgkhqbn7dVB0sZAAK-xjEb9sBJSNfCT0vI1u3bAQADAgADeAADJAQ',
                'profit_in_percentage' => 10,
                'has_options' => 1,
                'created_at' => '2023-01-03 15:02:07',
                'updated_at' => '2023-01-03 15:32:18',
            ),
            1 => 
            array (
                'id' => 7,
                'user_id' => 4,
                'category_id' => 2,
                'price' => 25000,
                'status' => '1',
                'photo_id' => 'AgACAgIAAxkBAAI3B2OJPwXkT1Q2gWHgkhqbn7dVB0sZAAK-xjEb9sBJSNfCT0vI1u3bAQADAgADeAADJAQ',
                'profit_in_percentage' => 4,
                'has_options' => 1,
                'created_at' => '2023-01-03 15:33:47',
                'updated_at' => '2023-01-03 15:33:47',
            ),
        ));
        
        
    }
}