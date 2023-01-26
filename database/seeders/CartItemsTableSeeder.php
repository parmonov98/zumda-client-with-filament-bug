<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CartItemsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('cart_items')->delete();
        
        
        
    }
}