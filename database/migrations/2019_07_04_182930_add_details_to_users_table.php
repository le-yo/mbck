<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDetailsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function(Blueprint $table)
        {
            $table->integer('session')->after('email')->nullable()->default(0);
            $table->integer('progress')->after('email')->nullable()->default(0);
            $table->integer('menu_id')->after('email')->nullable()->default(0);
            $table->integer('confirm_from')->after('email')->nullable()->default(0);
            $table->integer('menu_item_id')->after('email')->nullable()->default(0);
            $table->integer('difficulty_level')->after('email')->nullable()->default(0);
            $table->string('chasis',100)->after('email')->nullable();
            $table->string('phone',100)->after('email')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function(Blueprint $table)
        {
            $table->dropColumn('session');
            $table->dropColumn('progress');
            $table->dropColumn('menu_id');
            $table->dropColumn('confirm_from');
            $table->dropColumn('menu_item_id');
            $table->dropColumn('difficulty_level');
            $table->dropColumn('phone');
        });
    }
}
