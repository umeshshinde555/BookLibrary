<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRentBooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rent_books', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('u_id')->index();
            //$table->foreign('u_id')->references('u_id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('b_id')->index();
            //$table->foreign('b_id')->references('b_id')->on('books')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('rent_books');
    }
}
