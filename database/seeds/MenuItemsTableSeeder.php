<?php

use Illuminate\Database\Seeder;

// composer require laracasts/testdummy
use Laracasts\TestDummy\Factory as TestDummy;

class MenuItemsTableSeeder extends Seeder
{
    public function run()
    {
        Eloquent::unguard();
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('menu_items')->truncate();

        DB::table('menu_items')->delete();

        DB::table('menu_items')->insert(array(
            array(
                'menu_id' => 1,
                'description' => "Enter your name",
                'next_menu_id' => 2,
                'step' => 1,
                'confirmation_phrase' => 'Name',
            ),
            array(
                'menu_id' => 1,
                'description' => "Enter your Email",
                'next_menu_id' => 2,
                'step' => 2,
                'confirmation_phrase' => 'Email',
            ),
            array(
                'menu_id' => 1,
                'description' => 'Enter your Chasis No',
                'next_menu_id' => 3,
                'step' => 3,
                'confirmation_phrase' => 'Chasis',
            ),
            array(
                'menu_id' => 2,
                'description' => 'Pledge',
                'next_menu_id' => 3,
                'step' => 0,
                'confirmation_phrase' => '',
            ),
            array(
                'menu_id' => 3,
                'description' => 'Enter Amount',
                'next_menu_id' => 3,
                'step' => 1,
                'confirmation_phrase' => 'Amount',
            ),
            array(
                'menu_id' => 4,
                'description' => 'Enter Amount',
                'next_menu_id' => 3,
                'step' => 1,
                'confirmation_phrase' => 'Amount',
            ),
        ));
    }
}
