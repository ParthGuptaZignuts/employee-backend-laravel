<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 64);
            $table->string('email', 128);
            $table->string('website', 255)->nullable();
            $table->string('logo', 255)->nullable();
            $table->string('address', 256);
            $table->string('status');
            $table->softDeletes();
            $table->timestamps();
            $table->id('created_by')->nullable();
            $table->id('updated_by')->nullable();
            $table->id('deleted_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
