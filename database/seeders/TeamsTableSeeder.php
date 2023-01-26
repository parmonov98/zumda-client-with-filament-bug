<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TeamsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('teams')->delete();
        
        \DB::table('teams')->insert(array (
            0 => 
            array (
                'created_at' => '2022-11-30 05:48:01',
                'id' => 1,
                'name' => '\'s Team',
                'personal_team' => 1,
                'updated_at' => '2022-11-30 05:48:01',
                'user_id' => 1,
            ),
        ));
        
        
    }
}