<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RestaurantTranslationsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('restaurant_translations')->delete();
        
        \DB::table('restaurant_translations')->insert(array (
            0 => 
            array (
                'address' => 'palonchi ko\'cha 20-uy',
                'id' => 1,
                'lang' => 'uz',
                'name' => 'Bochka',
                'restaurant_id' => 1,
            ),
        ));
        
        
    }
}