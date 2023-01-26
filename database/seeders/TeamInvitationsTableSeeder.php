<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TeamInvitationsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('team_invitations')->delete();
        
        
        
    }
}