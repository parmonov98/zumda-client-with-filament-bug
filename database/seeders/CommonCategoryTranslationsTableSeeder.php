<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CommonCategoryTranslationsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('common_category_translations')->delete();
        
        \DB::table('common_category_translations')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'uz com',
                'lang' => 'uz',
                'common_category_id' => 1,
                'created_at' => '2023-01-03 08:55:51',
                'updated_at' => '2023-01-03 08:55:51',
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'ru com',
                'lang' => 'ru',
                'common_category_id' => 1,
                'created_at' => '2023-01-03 08:55:51',
                'updated_at' => '2023-01-03 08:55:51',
            ),
        ));
        
        
    }
}