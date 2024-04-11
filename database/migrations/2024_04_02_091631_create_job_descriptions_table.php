<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJobDescriptionsTable extends Migration
{
    public function up()
    {
        Schema::create('job_descriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->string('title', 64);
            $table->decimal('salary')->nullable();
            $table->string('employment_type', 64);
            $table->string('experience_required', 64);
            $table->string('skills_required', 64)->nullable();
            $table->date('posted_date')->default(now())->format('Y-m-d')->nullable();
            $table->date('expiry_date')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('job_descriptions');
    }
}
