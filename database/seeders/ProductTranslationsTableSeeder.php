<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductTranslationsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('product_translations')->delete();
        
        \DB::table('product_translations')->insert(array (
            0 => 
            array (
                'id' => 1,
                'lang' => 'uz',
                'name' => 'new pizza',
                'description' => 'doda-moda',
                'product_id' => NULL,
            ),
            1 => 
            array (
                'id' => 2,
                'lang' => 'uz',
                'name' => 'kombo',
                'description' => '6zs516cd5z1zs',
                'product_id' => NULL,
            ),
        ));
        
        
    }
}