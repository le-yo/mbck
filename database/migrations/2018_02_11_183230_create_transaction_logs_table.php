<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('phone', 20)->default(0);
            $table->string('client_name', 300)->default(0);
            $table->string('transaction_id', 100)->default(0);
            $table->double('amount', 8, 2)->default(0);
            $table->boolean('status')->default(0)->default(0);
            $table->string('account_no')->default(0);
            $table->string('transaction_time', 20)->default(0);
            $table->longText('details')->default(NULL);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaction_logs');
    }
}
