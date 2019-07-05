<?php

use Illuminate\Database\Seeder;

// composer require laracasts/testdummy
use Laracasts\TestDummy\Factory as TestDummy;

class MenusTableSeeder extends Seeder
{
    public function run()
    {
        //menu types type 0 - authentication mini app, Type 1 - another menu mini app, type 2 leads to a process app, 3 gives information directly
        Eloquent::unguard();
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('menus')->truncate();

        DB::table('menus')->delete();

        DB::table('menus')->insert(array(
            array(
                'title' => 'MBCK - Daimler Club Card Verification',
                'type' => 2,
                'confirmation_message' => "",
            ),
            array(
                'title' => "Chairman's Walk:",
                'type' => 1,
                'confirmation_message' => "Chairman's Walk",
            ),
            array(
                'title' => 'Contribute:',
                'type' => 2,
                'confirmation_message' => "Contribute",
            ),
            array(
                'title' => 'Pledge:',
                'type' => 2,
                'confirmation_message' => "Pledge",
            ),
        ));
    }
}
