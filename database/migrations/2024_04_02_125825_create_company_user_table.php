<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompanyUserTable extends Migration
{
    public function up()
    {
        Schema::create('company_user', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('user_id');
            $table->date('joining_date')->nullable();
            $table->integer('emp_number')->nullable();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('company_user');
    }
}
