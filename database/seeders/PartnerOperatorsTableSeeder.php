<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PartnerOperatorsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('partner_operators')->delete();
        
        \DB::table('partner_operators')->insert(array (
            0 => 
            array (
                'activation_code' => '165165',
                'activation_code_used' => 1,
                'created_at' => NULL,
                'id' => 1,
                'name' => 'partner oper',
                'restaurant_id' => 1,
                'self_status' => true,
                'status' => true,
                'telegram_id' => 65165165,
                'updated_at' => NULL,
                'username' => 'test+part',
            ),
        ));
        
        
    }
}