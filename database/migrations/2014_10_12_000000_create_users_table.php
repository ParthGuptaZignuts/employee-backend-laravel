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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name',64);
            $table->string('last_name',64);
            $table->string('email',128)->unique();
            $table->string('password');
            $table->enum('type', ['SA', 'CA', 'E', 'C'])->default('C')->comment('Super Admin,Employee,Company Admin,Employee');
            $table->text('address',255)->nullable(); 
            $table->string('city',32)->nullable(); 
            $table->date('dob')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('deleted_by')->nullable();
            $table->timestamps();
            $table->rememberToken();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
