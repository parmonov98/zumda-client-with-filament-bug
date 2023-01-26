<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RestaurantsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('restaurants')->delete();
        
        \DB::table('restaurants')->insert(array (
            0 => 
            array (
                'id' => 1,
                'name' => 'orzumurod kafe',
                'address' => 'Mehr-muruvvat',
                'latitude' => '37.22611347239868',
                'longitude' => '67.27750722886005',
                'phone_number' => '+998942638523',
                'payment_card' => NULL,
                'expiration_date' => NULL,
                'status' => 1,
                'deleted_at' => NULL,
                'created_at' => '2023-01-03 08:54:19',
                'updated_at' => '2023-01-03 08:54:19',
                'partner_id' => NULL,
                'operator_id' => NULL,
            ),
            1 => 
            array (
                'id' => 2,
                'name' => 'mmm',
                'address' => 'mmmmmmm',
                'latitude' => '37.22611347239868',
                'longitude' => '67.27750722886005',
                'phone_number' => '+696165165',
                'payment_card' => '8600330443121197',
                'expiration_date' => '12/24',
                'status' => 1,
                'deleted_at' => NULL,
                'created_at' => '2023-01-03 15:44:17',
                'updated_at' => '2023-01-03 15:46:34',
                'partner_id' => NULL,
                'operator_id' => NULL,
            ),
            2 => 
            array (
                'id' => 3,
                'name' => 'diplomat',
                'address' => 'diplomat address',
                'latitude' => '37.22611347239868',
                'longitude' => '67.27750722886005',
                'phone_number' => '+998942638523',
                'payment_card' => '8600330443121197',
                'expiration_date' => '12/24',
                'status' => 1,
                'deleted_at' => NULL,
                'created_at' => '2023-01-03 15:49:56',
                'updated_at' => '2023-01-03 15:49:56',
                'partner_id' => NULL,
                'operator_id' => NULL,
            ),
        ));
        
        
    }
}