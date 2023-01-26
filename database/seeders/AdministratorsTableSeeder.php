<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AdministratorsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('administrators')->delete();
        
        
        
    }
}