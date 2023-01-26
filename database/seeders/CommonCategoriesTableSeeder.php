<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CommonCategoriesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('common_categories')->delete();
        
        \DB::table('common_categories')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'uz com',
                'created_at' => '2023-01-03 08:55:51',
                'updated_at' => '2023-01-03 08:55:51',
            ),
        ));
        
        
    }
}