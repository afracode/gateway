<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGatewayTransactionsTable extends Migration
{


    public function up()
    {
        Schema::create('gateway_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('port');
            $table->integer('price');
            $table->string('ref_id', 100)->nullable();
            $table->string('tracking_code', 50)->nullable();
            $table->enum('status', [
                'INIT',
                'FAILED',
                'SUCCEED',
            ])->default('INIT');
            $table->string('ip', 20)->nullable();
            $table->foreignId('user_id')->nullable();
            $table->timestamp('payment_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

        });
    }



    public function down()
    {
        Schema::dropIfExists('gateway_transactions');
    }
}
