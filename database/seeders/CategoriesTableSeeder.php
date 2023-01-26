<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CategoriesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('categories')->delete();
        
        \DB::table('categories')->insert(array (
            0 => 
            array (
                'id' => 1,
                'user_id' => 4,
                'parent_id' => NULL,
                'status' => 1,
                'restaurant_id' => 1,
                'common_category_id' => 1,
                'created_at' => '2023-01-03 08:57:07',
                'updated_at' => '2023-01-03 08:57:07',
            ),
            1 => 
            array (
                'id' => 2,
                'user_id' => 4,
                'parent_id' => NULL,
                'status' => 1,
                'restaurant_id' => 1,
                'common_category_id' => NULL,
                'created_at' => '2023-01-03 09:03:11',
                'updated_at' => '2023-01-03 09:03:11',
            ),
            2 => 
            array (
                'id' => 3,
                'user_id' => 4,
                'parent_id' => NULL,
                'status' => 1,
                'restaurant_id' => 3,
                'common_category_id' => NULL,
                'created_at' => '2023-01-03 15:51:18',
                'updated_at' => '2023-01-03 15:51:18',
            ),
        ));
        
        
    }
}