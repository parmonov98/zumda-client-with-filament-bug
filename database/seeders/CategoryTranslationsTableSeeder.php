<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CategoryTranslationsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('category_translations')->delete();
        
        \DB::table('category_translations')->insert(array (
            0 => 
            array (
                'id' => 1,
                'category_id' => 1,
                'lang' => 'uz',
                'name' => 'uz cat',
                'description' => 'sdfsdf',
            ),
            1 => 
            array (
                'id' => 2,
                'category_id' => 1,
                'lang' => 'ru',
                'name' => 'ru cat',
                'description' => 'sdfsdf',
            ),
            2 => 
            array (
                'id' => 5,
                'category_id' => 2,
                'lang' => 'uz',
                'name' => 'uz cat 2',
                'description' => 'fwefwef',
            ),
            3 => 
            array (
                'id' => 6,
                'category_id' => 2,
                'lang' => 'ru',
                'name' => 'ru cat 2',
                'description' => 'sdfwe',
            ),
            4 => 
            array (
                'id' => 7,
                'category_id' => 5,
                'lang' => 'uz',
                'name' => 'uz pro2',
                'description' => 'sdfsdfsszdfs',
            ),
            5 => 
            array (
                'id' => 8,
                'category_id' => 5,
                'lang' => 'ru',
                'name' => 'ru pro',
                'description' => 'sdfsdfdfgfd',
            ),
            6 => 
            array (
                'id' => 43,
                'category_id' => 6,
                'lang' => 'uz',
                'name' => 'supo',
                'description' => 'ываываыва',
            ),
            7 => 
            array (
                'id' => 44,
                'category_id' => 6,
                'lang' => 'ru',
                'name' => 'суп',
                'description' => 'ывыапвыаы',
            ),
            8 => 
            array (
                'id' => 45,
                'category_id' => 7,
                'lang' => 'uz',
                'name' => 'uz sup',
                'description' => 'dfgdrdrg',
            ),
            9 => 
            array (
                'id' => 46,
                'category_id' => 7,
                'lang' => 'ru',
                'name' => 'ru sup',
                'description' => 'выаевупк',
            ),
            10 => 
            array (
                'id' => 47,
                'category_id' => 3,
                'lang' => 'uz',
                'name' => 'birinchi ovqatlar',
                'description' => 'bfrgthrthrt',
            ),
            11 => 
            array (
                'id' => 48,
                'category_id' => 3,
                'lang' => 'ru',
                'name' => 'первые',
                'description' => 'ываываыва',
            ),
        ));
        
        
    }
}